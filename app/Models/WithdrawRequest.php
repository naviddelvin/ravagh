<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawRequest extends Model
{
    protected $fillable = [
        'user_id', 'amount', 'bank_info', 'status', 'admin_note', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'bank_info' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
