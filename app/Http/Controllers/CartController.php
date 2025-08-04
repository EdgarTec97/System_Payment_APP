<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display the shopping cart.
     */
    public function index()
    {
        $cart = $this->getOrCreateCart();
        $cart->load(['items.product.primaryImage']);

        return view('cart.index', compact('cart'));
    }

    /**
     * Add product to cart.
     */
    public function add(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        if (!$product->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'El producto no está disponible.',
            ], 400);
        }

        if ($product->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'No hay suficiente stock disponible.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $cart = $this->getOrCreateCart();
            $item = $cart->addItem($product, $request->quantity);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto agregado al carrito.',
                'cart_count' => $cart->items_count,
                'item' => [
                    'id' => $item->id,
                    'product_title' => $item->product_title,
                    'quantity' => $item->quantity,
                    'formatted_total_price' => $item->formatted_total_price,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar el producto al carrito.',
            ], 500);
        }
    }

    /**
     * Update item quantity in cart.
     */
    public function updateQuantity(Request $request, int $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->getOrCreateCart();
        $item = $cart->items()->find($itemId);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado en el carrito.',
            ], 404);
        }

        if (!$item->product || !$item->product->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'El producto ya no está disponible.',
            ], 400);
        }

        if ($item->product->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'No hay suficiente stock disponible.',
                'available_stock' => $item->product->stock,
            ], 400);
        }

        DB::beginTransaction();

        try {
            $item->updateQuantity($request->quantity);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cantidad actualizada.',
                'item' => [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'formatted_total_price' => $item->formatted_total_price,
                ],
                'cart' => [
                    'formatted_subtotal' => $cart->fresh()->formatted_subtotal,
                    'formatted_total' => $cart->fresh()->formatted_total,
                    'items_count' => $cart->fresh()->items_count,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cantidad.',
            ], 500);
        }
    }

    /**
     * Remove item from cart.
     */
    public function remove(int $itemId)
    {
        $cart = $this->getOrCreateCart();

        DB::beginTransaction();

        try {
            $removed = $cart->removeItem($itemId);

            if (!$removed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado en el carrito.',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado del carrito.',
                'cart' => [
                    'formatted_subtotal' => $cart->fresh()->formatted_subtotal,
                    'formatted_total' => $cart->fresh()->formatted_total,
                    'items_count' => $cart->fresh()->items_count,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto.',
            ], 500);
        }
    }

    /**
     * Clear the entire cart.
     */
    public function clear()
    {
        $cart = $this->getOrCreateCart();

        DB::beginTransaction();

        try {
            $cart->items()->delete();
            $cart->recalculateTotal();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Carrito vaciado.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al vaciar el carrito.',
            ], 500);
        }
    }

    /**
     * Get cart count for header display.
     */
    public function count()
    {
        $cart = $this->getCurrentCart();
        
        return response()->json([
            'count' => $cart ? $cart->items_count : 0,
        ]);
    }

    /**
     * Proceed to checkout.
     */
    public function checkout()
    {
        $cart = $this->getOrCreateCart();

        if ($cart->items()->count() === 0) {
            return redirect()->route('cart.index')
                           ->withErrors(['cart' => 'Tu carrito está vacío.']);
        }

        // Validate stock for all items
        foreach ($cart->items as $item) {
            if (!$item->product || !$item->product->isAvailable()) {
                return redirect()->route('cart.index')
                               ->withErrors(['cart' => "El producto '{$item->product_title}' ya no está disponible."]);
            }

            if (!$item->hasEnoughStock()) {
                return redirect()->route('cart.index')
                               ->withErrors(['cart' => "No hay suficiente stock para '{$item->product_title}'."]);
            }
        }

        return view('cart.checkout', compact('cart'));
    }

    /**
     * Get or create cart for current user.
     */
    private function getOrCreateCart(): Order
    {
        $cart = $this->getCurrentCart();

        if (!$cart) {
            $cart = Order::create([
                'user_id' => Auth::id(),
                'status' => 'draft',
                'subtotal' => 0,
                'tax' => 0,
                'discount' => 0,
                'total' => 0,
                'currency' => 'USD',
            ]);
        }

        return $cart;
    }

    /**
     * Get current cart for user.
     */
    private function getCurrentCart(): ?Order
    {
        return Order::where('user_id', Auth::id())
                   ->where('status', 'draft')
                   ->first();
    }
}

