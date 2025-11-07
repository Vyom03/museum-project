<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'cart_token',
        'status',
        'payment_status',
        'currency',
        'subtotal',
        'tax_total',
        'shipping_total',
        'grand_total',
        'customer_name',
        'email',
        'phone',
        'country_code',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'notes' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
