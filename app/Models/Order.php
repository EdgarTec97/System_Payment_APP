<?php

namespace App\Models;

use App\Events\OrderStatusChanged;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Order extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'currency',
        'payment_method',
        'payment_status',
        'stripe_payment_intent_id',
        'billing_address',
        'shipping_address',
        'notes',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'payment_status', 'total', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate order number when creating
        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = self::generateOrderNumber();
            }
        });

        // Dispatch event when status changes
        static::updating(function ($order) {
            if ($order->isDirty('status')) {
                $previousStatus = $order->getOriginal('status');
                $newStatus = $order->status;

                // Update timestamp fields based on status
                switch ($newStatus) {
                    case 'paid':
                        $order->paid_at = now();
                        break;
                    case 'delivered':
                        $order->delivered_at = now();
                        break;
                    case 'cancelled':
                        $order->cancelled_at = now();
                        break;
                }

                // Dispatch event after the model is saved
                static::updated(function ($order) use ($previousStatus, $newStatus) {
                    event(new OrderStatusChanged($order, $previousStatus, $newStatus));
                });
            }
        });
    }

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . date('Y') . '-' . strtoupper(Str::random(8));
        } while (self::where('order_number', $number)->exists());

        return $number;
    }

    /**
     * Scope to filter orders by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter orders by date range.
     */
    public function scopeDateRange($query, $startDate = null, $endDate = null)
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope to search orders.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('order_number', 'ILIKE', "%{$search}%")
              ->orWhereHas('user', function ($userQuery) use ($search) {
                  $userQuery->where('name', 'ILIKE', "%{$search}%")
                           ->orWhere('email', 'ILIKE', "%{$search}%");
              })
              ->orWhereHas('items', function ($itemQuery) use ($search) {
                  $itemQuery->where('product_title', 'ILIKE', "%{$search}%");
              });
        });
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'created', 'paid']);
    }

    /**
     * Check if order can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if order is editable.
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if order is paid.
     */
    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'delivered']);
    }

    /**
     * Update order status.
     */
    public function updateStatus(string $status): bool
    {
        $validTransitions = [
            'draft' => ['created', 'cancelled'],
            'created' => ['paid', 'cancelled'],
            'paid' => ['delivered', 'cancelled'],
            'cancelled' => [],
            'delivered' => [],
        ];

        if (!in_array($status, $validTransitions[$this->status] ?? [])) {
            return false;
        }

        return $this->update(['status' => $status]);
    }

    /**
     * Add item to order.
     */
    public function addItem(Product $product, int $quantity): OrderItem
    {
        // Check if item already exists
        $existingItem = $this->items()->where('product_id', $product->id)->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity,
                'total_price' => ($existingItem->quantity + $quantity) * $existingItem->unit_price,
            ]);
            
            $this->recalculateTotal();
            return $existingItem;
        }

        // Create new item
        $item = $this->items()->create([
            'product_id' => $product->id,
            'product_title' => $product->title,
            'product_description' => $product->description,
            'quantity' => $quantity,
            'unit_price' => $product->final_price,
            'discount' => $product->discount,
            'total_price' => $quantity * $product->final_price,
            'product_snapshot' => [
                'title' => $product->title,
                'description' => $product->description,
                'price' => $product->price,
                'discount' => $product->discount,
                'final_price' => $product->final_price,
                'sku' => $product->sku,
            ],
        ]);

        $this->recalculateTotal();
        return $item;
    }

    /**
     * Remove item from order.
     */
    public function removeItem(int $itemId): bool
    {
        $item = $this->items()->find($itemId);
        
        if ($item) {
            $item->delete();
            $this->recalculateTotal();
            return true;
        }

        return false;
    }

    /**
     * Recalculate order totals.
     */
    public function recalculateTotal(): void
    {
        $subtotal = $this->items()->sum('total_price');
        $tax = $subtotal * 0.1; // 10% tax
        $total = $subtotal + $tax - $this->discount;

        $this->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => max(0, $total),
        ]);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'draft' => 'Borrador',
            'created' => 'Creada',
            'paid' => 'Pagada',
            'cancelled' => 'Cancelada',
            'delivered' => 'Entregada',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get status color class.
     */
    public function getStatusColorAttribute(): string
    {
        $colors = [
            'draft' => 'text-gray-600',
            'created' => 'text-blue-600',
            'paid' => 'text-green-600',
            'cancelled' => 'text-red-600',
            'delivered' => 'text-purple-600',
        ];

        return $colors[$this->status] ?? 'text-gray-600';
    }

    /**
     * Get formatted total.
     */
    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total, 2);
    }

    /**
     * Get formatted subtotal.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }

    /**
     * Get items count.
     */
    public function getItemsCountAttribute(): int
    {
        return $this->items()->sum('quantity');
    }
}

