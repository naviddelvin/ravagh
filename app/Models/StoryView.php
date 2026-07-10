<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoryView extends Model
{
    protected $fillable = [
        'story_id', 'user_id', 'watched_full', 'liked', 'shared',
        'commented', 'ip_address', 'device_id', 'reward_granted',
    ];

    protected function casts(): array
    {
        return [
            'watched_full' => 'boolean',
            'liked' => 'boolean',
            'shared' => 'boolean',
            'commented' => 'boolean',
            'reward_granted' => 'boolean',
        ];
    }

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
