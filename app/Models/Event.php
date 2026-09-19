<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'attendance_required' => 'boolean',
            'audience_snapshotted_at' => 'datetime',
            'attendance_finalized_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'location',
        'event_type',
        'attendance_required',
        'attendance_reward_points',
        'absence_penalty_points',
        'audience_snapshotted_at',
        'attendance_finalized_at',
    ];

    /**
     * Get all attendance records for this event.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function audienceRules(): HasMany
    {
        return $this->hasMany(EventAudienceRule::class);
    }

    public function attendanceExpectations(): HasMany
    {
        return $this->hasMany(EventAttendanceExpectation::class);
    }

    public function isAudienceLocked(): bool
    {
        return $this->audience_snapshotted_at !== null;
    }
}
