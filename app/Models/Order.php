<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'shop_id', 'coupon_id', 'order_number', 'status',
        'subtotal', 'discount_amount', 'total_amount', 'payment_method', 'is_paid',
        'cashback_applied',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'cashback_applied' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    // برچسب یکتا برای استفاده در توضیح تراکنش‌های کیف‌پول (مشابه Booking::referenceLabel)
    public function referenceLabel(): string
    {
        return "سفارش {$this->order_number}";
    }
}
