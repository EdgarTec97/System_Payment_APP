<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Support\ProductController as SupportProductController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductController extends SupportProductController
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'role:admin']);
    }

    /**
     * Display a listing of products including soft deleted.
     */
    public function index(Request $request)
    {
        $query = Product::with(['images', 'primaryImage']);

        // Include soft deleted if requested
        if ($request->filled('include_deleted') && $request->include_deleted) {
            $query->withTrashed();
        }

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'deleted':
                    $query->onlyTrashed();
                    break;
            }
        }

        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->inStock();
                    break;
                case 'low_stock':
                    $query->lowStock();
                    break;
                case 'out_of_stock':
                    $query->where('stock', 0);
                    break;
            }
        }

        if ($request->filled('min_price')) {
            $query->priceRange($request->min_price, null);
        }

        if ($request->filled('max_price')) {
            $query->priceRange(null, $request->max_price);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['created_at', 'updated_at', 'title', 'price', 'final_price', 'stock', 'deleted_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        /** @var LengthAwarePaginator $products */
        $products = $query->paginate(config('app.pagination_per_page', 15));
        $products->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        $product->load(['images', 'orderItems.order.user']);

        // Get activity log
        $activities = $product->activities()
            ->with('causer')
            ->latest()
            ->take(20)
            ->get();

        return view('admin.products.show', compact('product', 'activities'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        $product->load('images');

        return view('admin.products.edit', compact('product'));
    }

    /**
     * Force delete the specified product.
     */
    public function forceDelete(int $productId)
    {
        $product = Product::withTrashed()->findOrFail($productId);

        try {
            // Check if product has orders
            if ($product->orderItems()->exists()) {
                return redirect()->back()
                    ->withErrors(['error' => 'No se puede eliminar permanentemente el producto porque tiene órdenes asociadas.']);
            }

            // Delete all images
            $product->images()->each(function ($image) {
                $image->delete();
            });

            // Force delete the product
            $product->forceDelete();

            // Clear cache
            $this->clearProductCache();

            return redirect()->route('admin.products.index')
                ->with('success', 'Producto eliminado permanentemente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Error al eliminar el producto: ' . $e->getMessage()]);
        }
    }

    /**
     * Restore the specified product.
     */
    public function restore(int $productId)
    {
        $product = Product::withTrashed()->findOrFail($productId);

        try {
            $product->restore();

            // Clear cache
            $this->clearProductCache();

            return redirect()->back()
                ->with('success', 'Producto restaurado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Error al restaurar el producto: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk operations on products.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'products' => 'required|array',
            'products.*' => 'exists:products,id',
        ]);

        try {
            $products = Product::whereIn('id', $request->products);

            switch ($request->action) {
                case 'activate':
                    $products->update(['is_active' => true]);
                    $message = 'Productos activados exitosamente.';
                    break;

                case 'deactivate':
                    $products->update(['is_active' => false]);
                    $message = 'Productos desactivados exitosamente.';
                    break;

                case 'delete':
                    // Check if any product has orders
                    $productsWithOrders = $products->whereHas('orderItems')->count();
                    if ($productsWithOrders > 0) {
                        return redirect()->back()
                            ->withErrors(['error' => 'Algunos productos no se pueden eliminar porque tienen órdenes asociadas.']);
                    }

                    $products->delete();
                    $message = 'Productos eliminados exitosamente.';
                    break;
            }

            // Clear cache
            $this->clearProductCache();

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Error en la operación masiva: ' . $e->getMessage()]);
        }
    }

    /**
     * Get product statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'inactive_products' => Product::where('is_active', false)->count(),
            'deleted_products' => Product::onlyTrashed()->count(),
            'in_stock_products' => Product::inStock()->count(),
            'low_stock_products' => Product::lowStock()->count(),
            'out_of_stock_products' => Product::where('stock', 0)->count(),
            'total_stock_value' => Product::active()->sum(DB::raw('stock * final_price')),
            'average_price' => Product::active()->avg('final_price'),
        ];

        return response()->json($stats);
    }
}
