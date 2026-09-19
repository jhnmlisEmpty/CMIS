<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EventAttendanceExpectation extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_PRESENT, self::STATUS_ABSENT];

    protected $fillable = [
        'event_id', 'user_id', 'status', 'reward_points', 'penalty_points',
        'points_delta', 'score_after', 'finalized_at',
    ];

    protected function casts(): array
    {
        return ['finalized_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(SmallGroup::class, 'event_attendance_expectation_groups', 'expectation_id');
    }
}
