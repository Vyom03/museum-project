<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShoppingCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckoutController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'cart_token' => ['required', 'string'],
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country_code' => ['nullable', 'string', 'max:5'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'array'],
        ]);

        $cart = ShoppingCart::with('items.product')->where('token', $data['cart_token'])->first();

        if (!$cart) {
            throw new HttpException(404, 'Cart not found.');
        }

        if ($cart->items->isEmpty()) {
            throw new HttpException(422, 'Your cart is empty.');
        }

        $currency = $cart->currency;

        $order = DB::transaction(function () use ($cart, $data, $currency) {
            $this->assertInventory($cart->items);

            $subtotal = $cart->items->sum('line_total');
            $shipping = 0;
            $tax = 0;
            $grandTotal = $subtotal + $shipping + $tax;

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'cart_token' => $cart->token,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'currency' => $currency,
                'subtotal' => $subtotal,
                'tax_total' => $tax,
                'shipping_total' => $shipping,
                'grand_total' => $grandTotal,
                'customer_name' => $data['customer_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'country_code' => $data['country_code'] ?? null,
                'address_line1' => $data['address_line1'],
                'address_line2' => $data['address_line2'] ?? null,
                'city' => $data['city'],
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ]);

                $item->product->decrement('inventory_count', $item->quantity);
            }

            $cart->items()->delete();
            $cart->update([
                'items_count' => 0,
                'subtotal' => 0,
            ]);

            return $order;
        });

        $order->load('items');

        return new OrderResource($order);
    }

    private function assertInventory(iterable $items): void
    {
        foreach ($items as $item) {
            $available = $item->product->inventory_count;
            if ($available < $item->quantity) {
                throw new HttpException(422, $item->product->name . ' has only ' . $available . ' units left.');
            }
        }
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'VYOM-' . now()->format('Ymd');

        do {
            $number = $prefix . '-' . Str::upper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
