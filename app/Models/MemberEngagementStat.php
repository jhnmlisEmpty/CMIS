<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberEngagementStat extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'current_score', 'current_streak', 'longest_streak',
        'required_events', 'attended_events', 'missed_events', 'last_calculated_at',
    ];

    protected function casts(): array
    {
        return ['last_calculated_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
