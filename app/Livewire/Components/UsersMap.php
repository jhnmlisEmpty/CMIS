<?php

namespace App\Livewire\Components;

use App\Models\SmallGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public string $locationFilter = '';

    public string $birthdateFrom = '';

    public string $birthdateTo = '';

    public string $minAge = '';

    public string $maxAge = '';

    public string $roleFilter = '';

    public string $statusFilter = 'active';

    public string $smallGroupFilter = '';

    private const FILTER_PROPERTIES = [
        'search',
        'locationFilter',
        'birthdateFrom',
        'birthdateTo',
        'minAge',
        'maxAge',
        'roleFilter',
        'statusFilter',
        'smallGroupFilter',
    ];

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
            ->with(['smallGroups' => function ($query) {
                $query->where('small_group_members.status', 'active')
                    ->where('small_groups.status', 'active');
            }])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->when($this->locationFilter, function ($query) {
                $query->where(function ($q) {
                    $q->where('address', 'like', "%{$this->locationFilter}%")
                        ->orWhere('street_address', 'like', "%{$this->locationFilter}%")
                        ->orWhere('city_code', 'like', "%{$this->locationFilter}%")
                        ->orWhere('province_code', 'like', "%{$this->locationFilter}%")
                        ->orWhere('barangay_code', 'like', "%{$this->locationFilter}%");
                });
            })
            ->when($this->birthdateFrom, function ($query) {
                $query->whereDate('birthdate', '>=', $this->birthdateFrom);
            })
            ->when($this->birthdateTo, function ($query) {
                $query->whereDate('birthdate', '<=', $this->birthdateTo);
            })
            ->when($this->minAge !== '' || $this->maxAge !== '', function ($query) {
                $this->applyAgeFilter($query);
            })
            ->when($this->roleFilter, fn ($query) => $query->where('role', $this->roleFilter))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->smallGroupFilter, function ($query) {
                $query->whereHas('smallGroups', function ($groupQuery) {
                    $groupQuery->where('small_groups.id', $this->smallGroupFilter)
                        ->where('small_group_members.status', 'active');
                });
            })
            ->orderBy('name')
            ->get(['id', 'uuid', 'name', 'profile_photo_path', 'email', 'birthdate', 'phone', 'address', 'latitude', 'longitude', 'role', 'status']);
    }

    public function getUsersForMapProperty(): array
    {
        return $this->filteredUsers->map(function ($user) {
            return [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'profilePhotoUrl' => $user->profile_photo_path ? Storage::disk('public')->url($user->profile_photo_path) : null,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'birthdate' => $user->birthdate?->format('M j, Y'),
                'age' => $user->age,
                'latitude' => (float) $user->latitude,
                'longitude' => (float) $user->longitude,
                'role' => $user->role,
                'status' => $user->status,
                'smallGroup' => $user->smallGroups->first()?->name,
                'viewUrl' => route('users.show', $user),
            ];
        })->toArray();
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::FILTER_PROPERTIES, true)) {
            $this->dispatchMapUpdate();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->locationFilter = '';
        $this->birthdateFrom = '';
        $this->birthdateTo = '';
        $this->minAge = '';
        $this->maxAge = '';
        $this->roleFilter = '';
        $this->statusFilter = 'active';
        $this->smallGroupFilter = '';

        $this->dispatchMapUpdate();
    }

    public function hasActiveFilters(): bool
    {
        foreach (self::FILTER_PROPERTIES as $property) {
            if ($this->{$property} !== '') {
                return true;
            }
        }

        return false;
    }

    protected function dispatchMapUpdate(): void
    {
        $this->dispatch(
            'users-map-updated',
            componentId: $this->getId(),
            users: $this->usersForMap,
        );
    }

    protected function applyAgeFilter($query): void
    {
        $minAge = $this->minAge !== '' ? (int) $this->minAge : null;
        $maxAge = $this->maxAge !== '' ? (int) $this->maxAge : null;

        $query->whereNotNull('birthdate');

        if ($minAge !== null) {
            $query->whereRaw($this->ageSqlExpression().' >= ?', [$minAge]);
        }

        if ($maxAge !== null) {
            $query->whereRaw($this->ageSqlExpression().' <= ?', [$maxAge]);
        }
    }

    protected function ageSqlExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "((CAST(strftime('%Y', 'now') AS INTEGER) - CAST(strftime('%Y', birthdate) AS INTEGER)) - (CASE WHEN strftime('%m-%d', 'now') < strftime('%m-%d', birthdate) THEN 1 ELSE 0 END))";
        }

        if ($driver === 'pgsql') {
            return '(EXTRACT(YEAR FROM AGE(CURRENT_DATE, birthdate)))';
        }

        return '(TIMESTAMPDIFF(YEAR, birthdate, CURDATE()))';
    }

    public function render()
    {
        return view('livewire.components.users-map', [
            'users' => $this->filteredUsers,
            'usersForMap' => $this->usersForMap,
            'roles' => User::ROLES,
            'statuses' => User::STATUSES,
            'smallGroups' => SmallGroup::query()->active()->orderBy('name')->get(),
            'totalWithLocation' => User::whereNotNull('latitude')->whereNotNull('longitude')->count(),
        ]);
    }
}
