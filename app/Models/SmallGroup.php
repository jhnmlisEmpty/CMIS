<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmallGroup extends Model
{
    use HasFactory;

    /**
     * Status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'photo_path',
        'description',
        'leader_id',
        'status',
    ];

    /**
     * Get the leader of the small group.
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * Get the members of the small group.
     */
    public function members(): HasMany
    {
        return $this->hasMany(SmallGroupMember::class);
    }

    /**
     * Get active members of the small group.
     */
    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', 'active');
    }

    /**
     * Get the lessons of the small group.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(SmallGroupLesson::class)->orderBy('order');
    }

    /**
     * Get published lessons of the small group.
     */
    public function publishedLessons(): HasMany
    {
        return $this->lessons()->where('status', 'published');
    }

    /**
     * Check if small group is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Scope for active small groups.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
