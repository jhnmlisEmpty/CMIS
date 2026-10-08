<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use Auditable;

    public const TYPE_MEMBER = 'member';

    public const TYPE_SMALL_GROUP = 'small_group';

    public const TYPE_EVENT = 'event';

    public const TYPE_LESSON = 'lesson';

    public const TYPES = [
        self::TYPE_MEMBER,
        self::TYPE_SMALL_GROUP,
        self::TYPE_EVENT,
        self::TYPE_LESSON,
    ];

    protected $fillable = [
        'type',
        'name',
        'normalized_name',
    ];

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'taggable')->withTimestamps();
    }

    public function smallGroups(): MorphToMany
    {
        return $this->morphedByMany(SmallGroup::class, 'taggable')->withTimestamps();
    }

    public function events(): MorphToMany
    {
        return $this->morphedByMany(Event::class, 'taggable')->withTimestamps();
    }

    public function lessons(): MorphToMany
    {
        return $this->morphedByMany(Lesson::class, 'taggable')->withTimestamps();
    }
}
