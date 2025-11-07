<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'email',
        'currency',
        'items_count',
        'subtotal',
    ];

    protected $casts = [
        'items_count' => 'integer',
        'subtotal' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function refreshTotals(): void
    {
        $this->load(['items.product']);

        $subtotal = $this->items->sum('line_total');
        $this->forceFill([
            'subtotal' => $subtotal,
            'items_count' => $this->items->sum('quantity'),
        ])->save();
    }
}
