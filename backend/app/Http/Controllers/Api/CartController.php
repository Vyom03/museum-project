<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ShoppingCart;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CartController extends Controller
{
    public function ensure(Request $request)
    {
        $cart = $this->resolveCart($request, createIfMissing: true);

        $cart->load('items.product.images');

        return new CartResource($cart);
    }

    public function addItem(Request $request)
    {
        $data = $request->validate([
            'cart_token' => ['nullable', 'string'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->resolveCart($request, $data['cart_token'] ?? null, true);

        $product = Product::active()->findOrFail($data['product_id']);
        $quantity = $data['quantity'];

        $existingItem = $cart->items()->where('product_id', $product->id)->first();

        $newQuantity = $existingItem ? $existingItem->quantity + $quantity : $quantity;

        $this->assertInventory($product, $newQuantity);

        $unitPrice = $product->price;

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $newQuantity,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $newQuantity,
            ]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $quantity,
            ]);
        }

        $cart->refreshTotals();
        $cart->load('items.product.images');

        return new CartResource($cart);
    }

    public function updateItem(Request $request, CartItem $item)
    {
        $data = $request->validate([
            'cart_token' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->resolveCart($request, $data['cart_token']);

        if ($item->shopping_cart_id !== $cart->id) {
            throw new HttpException(403, 'Cart item does not belong to this cart.');
        }

        $product = $item->product()->firstOrFail();

        $this->assertInventory($product, $data['quantity']);

        $item->update([
            'quantity' => $data['quantity'],
            'unit_price' => $product->price,
            'line_total' => $product->price * $data['quantity'],
        ]);

        $cart->refreshTotals();
        $cart->load('items.product.images');

        return new CartResource($cart);
    }

    public function removeItem(Request $request, CartItem $item)
    {
        $data = $request->validate([
            'cart_token' => ['required', 'string'],
        ]);

        $cart = $this->resolveCart($request, $data['cart_token']);

        if ($item->shopping_cart_id !== $cart->id) {
            throw new HttpException(403, 'Cart item does not belong to this cart.');
        }

        $item->delete();

        $cart->refreshTotals();
        $cart->load('items.product.images');

        return new CartResource($cart);
    }

    private function resolveCart(Request $request, ?string $token = null, bool $createIfMissing = false): ShoppingCart
    {
        $token = $token
            ?? ($request->has('cart_token') ? (string) $request->string('cart_token')->value() : null)
            ?? $request->header('X-Cart-Token');

        if (is_string($token) && trim($token) === '') {
            $token = null;
        }

        if ($token) {
            $cart = ShoppingCart::firstWhere('token', $token);
            if ($cart) {
                return $cart;
            }

            if (!$createIfMissing) {
                throw new HttpException(404, 'Cart not found.');
            }
        }

        if (!$createIfMissing) {
            throw new HttpException(400, 'Missing cart token.');
        }

        return ShoppingCart::create([
            'token' => (string) Str::uuid(),
            'currency' => 'INR',
            'items_count' => 0,
            'subtotal' => 0,
        ]);
    }

    private function assertInventory(Product $product, int $requestedQuantity): void
    {
        if ($product->inventory_count < $requestedQuantity) {
            throw new HttpException(422, 'Only ' . $product->inventory_count . ' units available.');
        }
    }
}
