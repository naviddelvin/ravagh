<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'balance'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // واریز اعتبار (پاداش، شارژ و ...)
    public function credit(int $amount, string $source, string $description = null, $reference = null): WalletTransaction
    {
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'type' => 'credit',
            'amount' => $amount,
            'source' => $source,
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    // برداشت اعتبار (پرداخت سفارش، خدمات، تبلیغات و ...)
    // توجه: طبق قوانین کیف پول، این اعتبار قابل برداشت نقدی توسط کاربر عادی نیست
    // و فقط داخل پلتفرم قابل مصرف است.
    public function debit(int $amount, string $source, string $description = null, $reference = null): WalletTransaction
    {
        if ($this->balance < $amount) {
            throw new \RuntimeException('موجودی کیف پول کافی نیست.');
        }

        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'type' => 'debit',
            'amount' => $amount,
            'source' => $source,
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }
}
