<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'role:support,admin']);
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $query = Product::with(['images', 'primaryImage']);

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
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

        $allowedSorts = ['created_at', 'updated_at', 'title', 'price', 'final_price', 'stock'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }


        /** @var LengthAwarePaginator $products */
        $products = $query->paginate(config('app.pagination_per_page', 15));
        $products->withQueryString();

        return view('support.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        return view('support.products.create');
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            // Create product
            $product = Product::create([
                'title' => $request->title,
                'description' => $request->description,
                'stock' => $request->stock,
                'price' => $request->price,
                'discount' => $request->discount ?? 0,
                'is_active' => $request->boolean('is_active', true),
                'sku' => $request->sku ?: $this->generateSku($request->title),
                'metadata' => $request->metadata ? json_decode($request->metadata, true) : null,
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                $this->handleImageUploads($product, $request->file('images'));
            }

            DB::commit();

            // Clear cache
            $this->clearProductCache();

            return redirect()->route('support.products.show', $product)
                ->with('success', 'Producto creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => 'Error al crear el producto: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        $product->load(['images', 'orderItems.order']);

        return view('support.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        $product->load('images');

        return view('support.products.edit', compact('product'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validator = $this->validator($request->all(), $product->id);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            // Update product
            $product->update([
                'title' => $request->title,
                'description' => $request->description,
                'stock' => $request->stock,
                'price' => $request->price,
                'discount' => $request->discount ?? 0,
                'is_active' => $request->boolean('is_active', true),
                'sku' => $request->sku,
                'metadata' => $request->metadata ? json_decode($request->metadata, true) : null,
            ]);

            // Handle new image uploads
            if ($request->hasFile('images')) {
                $this->handleImageUploads($product, $request->file('images'));
            }

            // Handle image deletions
            if ($request->filled('delete_images')) {
                $imagesToDelete = explode(',', $request->delete_images);
                ProductImage::whereIn('id', $imagesToDelete)
                    ->where('product_id', $product->id)
                    ->get()
                    ->each(function ($image) {
                        $image->delete();
                    });
            }

            DB::commit();

            // Clear cache
            $this->clearProductCache();

            return redirect()->route('support.products.show', $product)
                ->with('success', 'Producto actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => 'Error al actualizar el producto: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        try {
            // Check if product has orders
            if ($product->orderItems()->exists()) {
                return redirect()->back()
                    ->withErrors(['error' => 'No se puede eliminar el producto porque tiene órdenes asociadas.']);
            }

            $product->delete();

            // Clear cache
            $this->clearProductCache();

            return redirect()->route('support.products.index')
                ->with('success', 'Producto eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Error al eliminar el producto: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle product status.
     */
    public function toggleStatus(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'activado' : 'desactivado';

        // Clear cache
        $this->clearProductCache();

        return redirect()->back()
            ->with('success', "Producto {$status} exitosamente.");
    }

    /**
     * Get a validator for product data.
     */
    protected function validator(array $data, ?int $productId = null)
    {
        $skuRule = 'nullable|string|max:100|unique:products,sku';
        if ($productId) {
            $skuRule .= ",{$productId}";
        }

        return Validator::make($data, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'stock' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sku' => [$skuRule],
            'is_active' => ['boolean'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'metadata' => ['nullable', 'json'],
        ], [
            'title.required' => 'El título es obligatorio.',
            'description.required' => 'La descripción es obligatoria.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio no puede ser negativo.',
            'discount.numeric' => 'El descuento debe ser un número.',
            'discount.max' => 'El descuento no puede ser mayor al 100%.',
            'sku.unique' => 'Este SKU ya está en uso.',
            'images.*.image' => 'Los archivos deben ser imágenes.',
            'images.*.mimes' => 'Las imágenes deben ser de tipo: jpeg, png, jpg, gif.',
            'images.*.max' => 'Las imágenes no pueden ser mayores a 2MB.',
            'metadata.json' => 'Los metadatos deben ser un JSON válido.',
        ]);
    }

    /**
     * Handle image uploads.
     */
    protected function handleImageUploads(Product $product, array $images)
    {
        $maxImages = config('app.max_product_images', 3);
        $currentImageCount = $product->images()->count();

        foreach ($images as $index => $image) {
            if ($currentImageCount >= $maxImages) {
                break;
            }

            $path = $image->store('products', 's3');

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'alt_text' => $product->title . ' - Imagen ' . ($currentImageCount + 1),
                'sort_order' => $currentImageCount,
                'is_primary' => $currentImageCount === 0,
            ]);

            $currentImageCount++;
        }
    }

    /**
     * Generate SKU for product.
     */
    protected function generateSku(string $title): string
    {
        $base = Str::upper(Str::slug($title, ''));
        $base = substr($base, 0, 6);

        do {
            $sku = $base . rand(1000, 9999);
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * Clear product-related cache.
     */
    protected function clearProductCache()
    {
        Cache::forget('products_price_range');
        Cache::tags(['products'])->flush();
    }
}
