<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Role constants
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_PASTOR = 'pastor';
    public const ROLE_MINISTRY_HEAD = 'ministry_head';
    public const ROLE_SMALL_GROUP_LEADER = 'small_group_leader';
    public const ROLE_MEMBER = 'member';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_PASTOR,
        self::ROLE_MINISTRY_HEAD,
        self::ROLE_SMALL_GROUP_LEADER,
        self::ROLE_MEMBER,
    ];

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
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
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
    public function ledSmallGroups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SmallGroup::class, 'leader_id');
    }

    /**
     * Get the small group memberships for this user.
     */
    public function smallGroupMemberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SmallGroupMember::class);
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
