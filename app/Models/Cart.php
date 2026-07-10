<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    // مجموع مبلغ سبد خرید (بدون تخفیف)
    public function getTotalAttribute(): int
    {
        return $this->items->sum(fn (CartItem $item) => $item->quantity * $item->product->final_price);
    }
}
