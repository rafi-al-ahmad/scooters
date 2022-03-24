<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    protected function validationRules()
    {
        return [
            'quantity' => ['required', 'numeric'],
            'variant' => ['required', 'exists:variants,id,deleted_at,NULL'],
        ];
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addToCart(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, array_merge($this->validationRules(), []))->validate();

        $variant = Variant::findOrFail($data['variant']);

        $cart = $this->getCart();
        $cart->add($variant, $variant->id, $data["quantity"]);

        if (auth('sanctum')->check()) {
            $this->setCartInDatabase($cart->toArray());
        }

        return $this->show()->cookie(
            'cart',
            $cart->encode(),
            525600 // 1 year
        );
    }

    public function removeFromCart(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, array_merge($this->validationRules(), []))->validate();

        $variant = Variant::findOrFail($data['variant']);

        $cart = $this->getCart();
        $cart->remove($variant, $variant->id, $data["quantity"]);

        if (auth('sanctum')->check()) {
            $this->setCartInDatabase($cart->toArray());
        }
        
        return response($cart->toArray())->cookie(
            'cart',
            $cart->encode(),
            525600 // 1 year
        );
        dd($cart->toArray());
    }

    protected function getDatabaseCart()
    {
        $cart = ["items" => []];
        if (auth('sanctum')->check()) {
            $databaseCartItems = CartItem::where('user_id', $this->currentUserId())->get();

            foreach ($databaseCartItems as  $databaseCartItem) {
                $cart["items"][$databaseCartItem->variant_id] = [
                    "quantity" => $databaseCartItem->quantity,
                    "item_id" => $databaseCartItem->variant_id,
                    "price" => $databaseCartItem->variant->price,
                    "total" => $databaseCartItem->variant->price * $databaseCartItem->quantity,
                ];
            }
        }
        return $cart;
    }

    protected function setCartInDatabase($cart)
    {
        // delete items wich is deleted from cart
        CartItem::whereNotIn('variant_id', array_keys($cart["items"]))
            ->where('user_id', $this->currentUserId())->delete();

        foreach ($cart["items"] as  $cartItem) {
            $item = CartItem::updateOrCreate([
                "user_id" => $this->currentUserId(),
                "variant_id" => $cartItem["item_id"],
            ], [
                "quantity" => $cartItem["quantity"],
            ]);
        }

        return true;
    }

    public function getCart()
    {
        $cookiesCart = Cookie::has('cart') ? Cookie::get('cart') : null;
        if ($cookiesCart) {
            $cookiesCart = json_decode($cookiesCart, true);
        }

        return new Cart($this->getMergedCart($cookiesCart, $this->getDatabaseCart()));
    }

    protected function getMergedCart($newCart, array $databaseCart)
    {
        $globalCart = [
            "items" => []
        ];

        if ($newCart) {
            $globalCart = $newCart;
        } else {
            $newCart = $globalCart;
        }

        foreach ($databaseCart["items"] as $itemKey => $cartItem) {
            if (!array_key_exists($itemKey, $newCart["items"])) {
                $globalCart["items"][$itemKey] = $cartItem;
            }
        }

        return $globalCart;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $storedCart = $this->getCart();
        $variants = $storedCart->loadProducts();
        $subtotal = 0;
        $cart = [];

        foreach ($variants as $variant) {
            $cart["items"][$variant->id] = [
                "quantity" => $storedCart->items[$variant->id]["quantity"],
                "total" => $storedCart->items[$variant->id]["quantity"] * $variant->price,
                "item" => [
                    "id" => $variant->id,
                    "title" => $variant->product->title,
                    "description" => $variant->product->description,
                    "sku" => $variant->sku,
                    "options" => $variant->options,
                    "compareAtPrice" => $variant->compareAtPrice,
                    "price" => $variant->price,
                    "product_type" => $variant->product->product_type,
                    "avilable_quantity" => $variant->quantity,
                    "image" => $variant->image,
                    "main_image" => $variant->product->getMainImage(),
                ]
            ];
            $subtotal += $cart["items"][$variant->id]['total'];
        }

        $cart["subtotal"] = $subtotal;

        return response($cart);
    }

}
