<?php

namespace App\Livewire\Components;

use App\Models\User;
use Livewire\Component;

class UsersMap extends Component
{
    // Accept users collection or query filters
    public bool $showFilters = true;
    public bool $showLegend = true;
    public bool $showMemberList = true;
    public string $height = '500px';
    
    // Filters
    public string $search = '';
    public string $roleFilter = '';
    public string $statusFilter = 'active';

    public function mount(
        bool $showFilters = true,
        bool $showLegend = true,
        bool $showMemberList = true,
        string $height = '500px',
        string $statusFilter = 'active'
    ): void {
        $this->showFilters = $showFilters;
        $this->showLegend = $showLegend;
        $this->showMemberList = $showMemberList;
        $this->height = $height;
        $this->statusFilter = $statusFilter;
    }

    public function getFilteredUsersProperty()
    {
        return User::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%")
                      ->orWhere('address', 'like', "%{$this->search}%");
                });
            })
            ->when($this->roleFilter, function ($query) {
                $query->where('role', $this->roleFilter);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->get(['id', 'uuid', 'name', 'profile_photo_path', 'email', 'phone', 'address', 'latitude', 'longitude', 'role', 'status']);
    }

    public function getUsersForMapProperty(): array
    {
        return $this->filteredUsers->map(function ($user) {
            return [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'profilePhotoUrl' => $user->profile_photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_photo_path) : null,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'latitude' => (float) $user->latitude,
                'longitude' => (float) $user->longitude,
                'role' => $user->role,
                'status' => $user->status,
                'viewUrl' => route('users.show', $user),
            ];
        })->toArray();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->statusFilter = 'active';
    }

    public function render()
    {
        return view('livewire.components.users-map', [
            'users' => $this->filteredUsers,
            'usersForMap' => $this->usersForMap,
            'roles' => User::ROLES,
            'statuses' => User::STATUSES,
            'totalWithLocation' => User::whereNotNull('latitude')->whereNotNull('longitude')->count(),
        ]);
    }
}
