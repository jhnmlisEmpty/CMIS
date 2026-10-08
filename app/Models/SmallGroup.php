<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Services\AccessManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class SmallGroup extends Model
{
    use Auditable, HasFactory;

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
        'status',
    ];

    /**
     * Get the leaders of the small group.
     */
    public function leaders(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'small_group_leaders')
            ->withTimestamps();
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->where('tags.type', Tag::TYPE_SMALL_GROUP)
            ->withTimestamps();
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

    public function scopeVisibleTo(Builder $query, ?User $viewer, string $permission = 'small_groups.view'): Builder
    {
        return app(AccessManager::class)->scopeSmallGroups($query, $viewer, $permission);
    }
}
