<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use Auditable;

    public const ADMIN = 'admin';

    public const PASTOR = 'pastor';

    public const MINISTRY_HEAD = 'ministry_head';

    public const SMALL_GROUP_LEADER = 'small_group_leader';

    public const MEMBER = 'member';

    public const SYSTEM_SLUGS = [
        self::ADMIN,
        self::PASTOR,
        self::MINISTRY_HEAD,
        self::SMALL_GROUP_LEADER,
        self::MEMBER,
    ];

    protected $fillable = ['name', 'slug', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean', 'permissions_version' => 'integer'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    public static function defaultForNewUsers(?int $excludingRoleId = null): ?self
    {
        return static::query()
            ->when($excludingRoleId, fn ($query) => $query->whereKeyNot($excludingRoleId))
            ->where('slug', self::MEMBER)
            ->first();
    }
}
