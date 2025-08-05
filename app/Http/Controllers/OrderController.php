<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display a listing of user's orders.
     */
    public function index(Request $request)
    {
        $query = Order::with(['items.product.primaryImage'])
            ->where('user_id', Auth::id())
            ->where('status', '!=', 'draft');

        // Apply filters
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('order_number')) {
            $query->where('order_number', 'ILIKE', '%' . $request->order_number . '%');
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('min_total')) {
            $query->where('total', '>=', $request->min_total);
        }

        if ($request->filled('max_total')) {
            $query->where('total', '<=', $request->max_total);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['created_at', 'order_number', 'status', 'total'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        /** @var LengthAwarePaginator $orders */
        $orders = $query->paginate(config('app.pagination_per_page', 15));
        $orders->withQueryString();

        return view('orders.index', compact('orders'));
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        // Verify order belongs to current user
        if ($order->user_id !== Auth::id()) {
            abort(404);
        }

        $order->load(['items.product.primaryImage']);

        return view('orders.show', compact('order'));
    }

    /**
     * Cancel the specified order.
     */
    public function cancel(Order $order)
    {
        // Verify order belongs to current user
        if ($order->user_id !== Auth::id()) {
            abort(404);
        }

        if (!$order->canBeCancelled()) {
            return redirect()->back()
                ->withErrors(['error' => 'Esta orden no puede ser cancelada.']);
        }

        try {
            // If order was paid, restore stock
            if ($order->isPaid()) {
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increaseStock($item->quantity);
                    }
                }
            }

            $order->updateStatus('cancelled');

            return redirect()->back()
                ->with('success', 'Orden cancelada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Error al cancelar la orden.']);
        }
    }

    /**
     * Delete the specified order (only if cancelled).
     */
    public function destroy(Order $order)
    {
        // Verify order belongs to current user
        if ($order->user_id !== Auth::id()) {
            abort(404);
        }

        if (!$order->canBeDeleted()) {
            return redirect()->back()
                ->withErrors(['error' => 'Solo se pueden eliminar órdenes canceladas.']);
        }

        try {
            $order->delete();

            return redirect()->route('orders.index')
                ->with('success', 'Orden eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Error al eliminar la orden.']);
        }
    }

    /**
     * Download order invoice (PDF).
     */
    public function invoice(Order $order)
    {
        // Verify order belongs to current user
        if ($order->user_id !== Auth::id()) {
            abort(404);
        }

        // Only allow invoice download for paid orders
        if (!$order->isPaid()) {
            return redirect()->back()
                ->withErrors(['error' => 'Solo se pueden descargar facturas de órdenes pagadas.']);
        }

        $order->load(['items.product', 'user']);

        // Generate PDF using a view
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('orders.invoice', compact('order'));

        return $pdf->download("factura-{$order->order_number}.pdf");
    }

    /**
     * Track order status.
     */
    public function track(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
        ]);

        $order = Order::where('order_number', $request->order_number)
            ->where('user_id', Auth::id())
            ->first();

        if (!$order) {
            return redirect()->back()
                ->withErrors(['order_number' => 'Número de orden no encontrado.']);
        }

        return redirect()->route('orders.show', $order);
    }

    /**
     * Get order statistics for user dashboard.
     */
    public function statistics()
    {
        $userId = Auth::id();

        $stats = [
            'total_orders' => Order::where('user_id', $userId)->where('status', '!=', 'draft')->count(),
            'pending_orders' => Order::where('user_id', $userId)->whereIn('status', ['created', 'paid'])->count(),
            'completed_orders' => Order::where('user_id', $userId)->where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('user_id', $userId)->where('status', 'cancelled')->count(),
            'total_spent' => Order::where('user_id', $userId)->where('status', '!=', 'draft')->sum('total'),
        ];

        return response()->json($stats);
    }
}
