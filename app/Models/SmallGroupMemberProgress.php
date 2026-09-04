<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmallGroupMemberProgress extends Model
{
    use HasFactory;

    /**
     * Status constants
     */
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_NOT_STARTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'small_group_member_id',
        'small_group_lesson_id',
        'status',
        'completed_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the member.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(SmallGroupMember::class, 'small_group_member_id');
    }

    /**
     * Get the lesson.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(SmallGroupLesson::class, 'small_group_lesson_id');
    }

    /**
     * Check if progress is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if progress is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if progress is not started.
     */
    public function isNotStarted(): bool
    {
        return $this->status === self::STATUS_NOT_STARTED;
    }

    /**
     * Mark as completed.
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as in progress.
     */
    public function markInProgress(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'completed_at' => null,
        ]);
    }
}
