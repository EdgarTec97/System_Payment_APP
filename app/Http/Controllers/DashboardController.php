<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verified');
    }

    /**
     * Show the basic user dashboard.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $stats = [
            'total_orders' => $user->orders()->count(),
            'pending_orders' => $user->orders()->where('status', 'created')->count(),
            'completed_orders' => $user->orders()->where('status', 'delivered')->count(),
            'total_spent' => $user->orders()->where('status', '!=', 'cancelled')->sum('total'),
        ];

        $recent_orders = $user->orders()
            ->with('items.product')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.index', compact('stats', 'recent_orders'));
    }

    /**
     * Show the admin dashboard.
     */
    public function admin()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'No tienes permisos para acceder a esta página.');
        }

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_products' => Product::count(),
            'low_stock_products' => Product::where('stock', '<=', 5)->count(),
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'created')->count(),
            'total_revenue' => Order::where('status', '!=', 'cancelled')->sum('total'),
            'monthly_revenue' => Order::where('status', '!=', 'cancelled')
                ->whereMonth('created_at', now()->month)
                ->sum('total'),
        ];

        $recent_orders = Order::with(['user', 'items.product'])
            ->latest()
            ->take(10)
            ->get();

        $recent_users = User::with('roles')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard.admin', compact('stats', 'recent_orders', 'recent_users'));
    }

    /**
     * Show the support dashboard.
     */
    public function support()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->hasRole('support') && !$user->hasRole('admin')) {
            abort(403, 'No tienes permisos para acceder a esta página.');
        }

        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'low_stock_products' => Product::where('stock', '<=', 5)->count(),
            'out_of_stock_products' => Product::where('stock', 0)->count(),
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'created')->count(),
            'processing_orders' => Order::where('status', 'paid')->count(),
            'monthly_orders' => Order::whereMonth('created_at', now()->month)->count(),
        ];

        $recent_orders = Order::with(['user', 'items.product'])
            ->latest()
            ->take(10)
            ->get();

        $low_stock_products = Product::where('stock', '<=', 5)
            ->where('stock', '>', 0)
            ->orderBy('stock')
            ->take(10)
            ->get();

        return view('dashboard.support', compact('stats', 'recent_orders', 'low_stock_products'));
    }
}
