<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\Auditable;
use App\Services\AccessManager;
use App\Support\PermissionRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Auditable, HasFactory, Notifiable;

    /**
     * Role constants
     */
    public const ROLE_ADMIN = Role::ADMIN;

    public const ROLE_PASTOR = Role::PASTOR;

    public const ROLE_MINISTRY_HEAD = Role::MINISTRY_HEAD;

    public const ROLE_SMALL_GROUP_LEADER = Role::SMALL_GROUP_LEADER;

    public const ROLE_MEMBER = Role::MEMBER;

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_PASTOR,
        self::ROLE_MINISTRY_HEAD,
        self::ROLE_SMALL_GROUP_LEADER,
        self::ROLE_MEMBER,
    ];

    /** Compatibility bridge for legacy seeders and factories that still pass one role slug. */
    protected ?string $pendingRoleSlug = null;

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
     * Gender constants
     */
    public const GENDER_MALE = 'male';

    public const GENDER_FEMALE = 'female';

    public const GENDERS = [
        self::GENDER_MALE,
        self::GENDER_FEMALE,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'profile_photo_path',
        'email',
        'password',
        'gender',
        'birthdate',
        'phone',
        'social_media_url',
        'address',
        'region_code',
        'province_code',
        'city_code',
        'barangay_code',
        'street_address',
        'latitude',
        'longitude',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });

        static::saved(function (User $user): void {
            $slug = $user->pendingRoleSlug;

            if ($slug !== null) {
                $role = Role::query()->where('slug', $slug)->first()
                    ?? Role::defaultForNewUsers();

                if ($role) {
                    $user->roles()->sync([$role->id]);
                    $user->unsetRelation('roles');
                }

                $user->pendingRoleSlug = null;
            } elseif (! $user->roles()->exists()) {
                $defaultRole = Role::defaultForNewUsers();
                if ($defaultRole) {
                    $user->roles()->attach($defaultRole->id);
                }
            }
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Transitional single-role accessor. New application code must use roles().
     */
    public function getRoleAttribute(): ?string
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->sortBy('id')->first()?->slug;
        }

        return $this->roles()->orderBy('roles.id')->value('slug');
    }

    public function setRoleAttribute(?string $role): void
    {
        $this->pendingRoleSlug = $role ?: Role::MEMBER;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('slug', $role);
        }

        return $this->roles()->where('slug', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->whereIn('slug', $roles)->isNotEmpty();
        }

        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isSmallGroupLeader(): bool
    {
        return $this->hasRole(self::ROLE_SMALL_GROUP_LEADER);
    }

    public function permissionScope(string $permission): ?string
    {
        return app(AccessManager::class)->permissionScope($this, $permission);
    }

    public function hasAllScope(string $permission): bool
    {
        return $this->permissionScope($permission) === PermissionRegistry::SCOPE_ALL;
    }

    /** Limit member records using the scope assigned to the requested action. */
    public function scopeVisibleTo(Builder $query, ?User $viewer, string $permission = 'users.view'): Builder
    {
        return app(AccessManager::class)->scopeMembers($query, $viewer, $permission);
    }

    public function canAccessMember(User $member, string $permission = 'users.view'): bool
    {
        return app(AccessManager::class)->canAccessMember($this, $member, $permission);
    }

    public function canAccessSmallGroup(SmallGroup $smallGroup, string $permission = 'small_groups.view'): bool
    {
        return app(AccessManager::class)->canAccessSmallGroup($this, $smallGroup, $permission);
    }

    public function leadsSmallGroup(SmallGroup $smallGroup): bool
    {
        return $smallGroup->leaders()->whereKey($this->id)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissionScope($permission) !== null;
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get age from birthdate.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birthdate?->age;
    }

    /**
     * Get the small groups this user leads.
     */
    public function ledSmallGroups(): BelongsToMany
    {
        return $this->belongsToMany(SmallGroup::class, 'small_group_leaders')
            ->withTimestamps();
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->where('tags.type', Tag::TYPE_MEMBER)
            ->withTimestamps();
    }

    /**
     * Get the small group memberships for this user.
     */
    public function smallGroupMemberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SmallGroupMember::class);
    }

    public function attendanceExpectations(): HasMany
    {
        return $this->hasMany(EventAttendanceExpectation::class);
    }

    public function engagementStat(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MemberEngagementStat::class);
    }

    /**
     * Get small groups this user is a member of (through memberships).
     */
    public function smallGroups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(SmallGroup::class, 'small_group_members')
            ->withPivot('status', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get the active small group this user belongs to.
     */
    public function getActiveSmallGroup(): ?SmallGroup
    {
        return $this->smallGroups()
            ->where('small_group_members.status', SmallGroupMember::STATUS_ACTIVE)
            ->where('small_groups.status', SmallGroup::STATUS_ACTIVE)
            ->first();
    }
}
