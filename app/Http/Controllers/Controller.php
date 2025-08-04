<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="E-Commerce API",
 *     version="1.0.0",
 *     description="API documentation for E-Commerce Laravel application with authentication, products, orders, and payment integration",
 *     @OA\Contact(
 *         email="admin@ecommerce.com",
 *         name="E-Commerce Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format: Bearer {token}"
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(
 *         property="roles",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Role")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="Role",
 *     type="object",
 *     title="Role",
 *     description="Role model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="ADMIN"),
 *     @OA\Property(property="description", type="string", example="Administrator role"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Product",
 *     description="Product model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Smartphone XYZ"),
 *     @OA\Property(property="description", type="string", example="High-quality smartphone with advanced features"),
 *     @OA\Property(property="sku", type="string", example="PHONE-001"),
 *     @OA\Property(property="price", type="number", format="float", example=599.99),
 *     @OA\Property(property="discount", type="integer", example=10),
 *     @OA\Property(property="final_price", type="number", format="float", example=539.99),
 *     @OA\Property(property="stock", type="integer", example=50),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="metadata", type="object", example={"category": "electronics", "brand": "TechCorp"}),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ProductImage")
 *     ),
 *     @OA\Property(property="primary_image", ref="#/components/schemas/ProductImage")
 * )
 * 
 * @OA\Schema(
 *     schema="ProductImage",
 *     type="object",
 *     title="Product Image",
 *     description="Product image model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="product_id", type="integer", example=1),
 *     @OA\Property(property="url", type="string", example="https://example.com/images/product1.jpg"),
 *     @OA\Property(property="alt_text", type="string", example="Smartphone front view"),
 *     @OA\Property(property="is_primary", type="boolean", example=true),
 *     @OA\Property(property="sort_order", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="Order",
 *     type="object",
 *     title="Order",
 *     description="Order model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="order_number", type="string", example="ORD-2023-001"),
 *     @OA\Property(property="status", type="string", enum={"draft", "created", "paid", "cancelled", "delivered"}, example="paid"),
 *     @OA\Property(property="subtotal", type="number", format="float", example=100.00),
 *     @OA\Property(property="tax", type="number", format="float", example=10.00),
 *     @OA\Property(property="total", type="number", format="float", example=110.00),
 *     @OA\Property(property="currency", type="string", example="USD"),
 *     @OA\Property(property="payment_method", type="string", example="stripe"),
 *     @OA\Property(property="payment_status", type="string", example="paid"),
 *     @OA\Property(property="stripe_payment_intent_id", type="string", example="pi_1234567890"),
 *     @OA\Property(property="notes", type="string", example="Special delivery instructions"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/OrderItem")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="OrderItem",
 *     type="object",
 *     title="Order Item",
 *     description="Order item model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order_id", type="integer", example=1),
 *     @OA\Property(property="product_id", type="integer", example=1),
 *     @OA\Property(property="product_title", type="string", example="Smartphone XYZ"),
 *     @OA\Property(property="product_sku", type="string", example="PHONE-001"),
 *     @OA\Property(property="quantity", type="integer", example=2),
 *     @OA\Property(property="unit_price", type="number", format="float", example=539.99),
 *     @OA\Property(property="total_price", type="number", format="float", example=1079.98),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="product", ref="#/components/schemas/Product")
 * )
 * 
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     title="API Response",
 *     description="Standard API response format",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation completed successfully"),
 *     @OA\Property(property="data", type="object"),
 *     @OA\Property(property="errors", type="object")
 * )
 * 
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     type="object",
 *     title="Paginated Response",
 *     description="Paginated API response format",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="meta", type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="from", type="integer", example=1),
 *         @OA\Property(property="last_page", type="integer", example=5),
 *         @OA\Property(property="per_page", type="integer", example=15),
 *         @OA\Property(property="to", type="integer", example=15),
 *         @OA\Property(property="total", type="integer", example=75)
 *     ),
 *     @OA\Property(property="links", type="object",
 *         @OA\Property(property="first", type="string", example="http://example.com/api/products?page=1"),
 *         @OA\Property(property="last", type="string", example="http://example.com/api/products?page=5"),
 *         @OA\Property(property="prev", type="string", example=null),
 *         @OA\Property(property="next", type="string", example="http://example.com/api/products?page=2")
 *     )
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}

