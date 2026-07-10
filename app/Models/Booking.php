<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id', 'shop_id', 'service_id', 'booking_number', 'service_name',
        'scheduled_at', 'duration_minutes', 'total_amount', 'payment_method',
        'is_paid', 'cashback_applied', 'status', 'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'is_paid' => 'boolean',
            'cashback_applied' => 'boolean',
            'reminder_sent_at' => 'datetime',
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

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    // برچسب یکتا برای استفاده در توضیح تراکنش‌های کیف‌پول (مشابه Order::referenceLabel)
    public function referenceLabel(): string
    {
        return "نوبت {$this->booking_number}";
    }
}
