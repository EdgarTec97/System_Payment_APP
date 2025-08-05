<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $query = Product::with(['images', 'primaryImage'])
            ->active();

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('min_price')) {
            $query->priceRange($request->min_price, null);
        }

        if ($request->filled('max_price')) {
            $query->priceRange(null, $request->max_price);
        }

        if ($request->filled('in_stock') && $request->in_stock) {
            $query->inStock();
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['created_at', 'title', 'price', 'final_price', 'stock'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->paginate(config('app.pagination_per_page', 15))
            ->withQueryString();

        // Get filter options for the view
        $priceRange = Cache::remember('products_price_range', 3600, function () {
            return [
                'min' => Product::active()->min('final_price') ?? 0,
                'max' => Product::active()->max('final_price') ?? 1000,
            ];
        });

        return view('products.index', compact('products', 'priceRange'));
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product, ProductCacheService $cacheService)
    {
        if (!$product->is_active) {
            abort(404);
        }

        // Use cached product data
        $product = $cacheService->getProduct($product->id);

        if (!$product) {
            abort(404);
        }

        // Get related products using cache
        $relatedProducts = $cacheService->getRelatedProducts($product, 4);

        return view('products.show', compact('product', 'relatedProducts'));
    }

    /**
     * Get products for AJAX requests.
     */
    public function search(Request $request)
    {
        $query = Product::with(['primaryImage'])
            ->active();

        if ($request->filled('q')) {
            $query->search($request->q);
        }

        $products = $query->take(10)->get();

        return response()->json([
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'price' => $product->formatted_final_price,
                    'image' => $product->primary_image_url,
                    'stock' => $product->stock,
                    'is_available' => $product->isAvailable(),
                ];
            }),
        ]);
    }
}
