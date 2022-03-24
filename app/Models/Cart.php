<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    public $items = [];

    public function  __Construct($cart = null)
    {
        if ($cart) {
            $this->items = $cart['items'];
        }
    }


    public function  add($item, $id, $quantity)
    {
        $storedItem = ['quantity' => 0, 'total' => 0, "price" => $item->price, 'item_id' => $item->id];

        if ($this->items) {
            if (array_key_exists($id, $this->items)) {
                $storedItem = $this->items[$id];
            }
        }

        $storedItem['quantity'] += $quantity;
        $storedItem['total'] = $item->price * $storedItem['quantity'];
        $this->items[$id] = $storedItem;
    }


    public function  remove($item, $id, $quantity)
    {
        if ($this->items) {
            if (array_key_exists($id, $this->items)) {
                if ($this->items[$id]['quantity'] - $quantity <= 0) {
                    unset($this->items[$id]);
                } else {
                    $this->items[$id]['quantity'] -= $quantity;
                    $this->items[$id]['total'] = $item->price * $this->items[$id]['quantity'];
                }
            }
        }
    }

    public function subtotal()
    {
        $subtotal = 0;

        foreach ($this->items as $item) {
            $subtotal += $item['total'];
        }

        return $subtotal;
    }

    public function encode()
    {
        return json_encode([
            "items" => $this->items,
            "subtotal" => $this->subtotal(),
        ]);
    }

    public function toArray()
    {
        return [
            "items" => $this->items,
            "subtotal" => $this->subtotal(),
        ];
    }

    public function loadProducts()
    {
        // dd($this->items);

        return Variant::whereIn("id", array_keys($this->items))->get();
    }
}
