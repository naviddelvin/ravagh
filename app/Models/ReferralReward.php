<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralReward extends Model
{
    protected $fillable = [
        'referrer_id', 'referred_id', 'trigger_type', 'trigger_id', 'referrer_amount', 'referred_amount',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function trigger()
    {
        return $this->morphTo();
    }
}
