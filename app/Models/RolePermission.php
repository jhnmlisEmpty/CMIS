<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
    use Auditable;

    public $timestamps = false;

    protected $fillable = ['role_id', 'role', 'permission', 'access_scope'];

    public function setRoleAttribute(string $slug): void
    {
        $this->attributes['role_id'] = Role::query()->where('slug', $slug)->value('id');
    }

    public function getRoleAttribute(): ?string
    {
        return $this->relationLoaded('role')
            ? $this->getRelation('role')?->slug
            : $this->role()->value('slug');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
