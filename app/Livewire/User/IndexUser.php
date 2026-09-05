<?php

namespace App\Livewire\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Members | True Vine World Harvest Church - Pangasinan')]
class IndexUser extends Component
{
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    public string $statusFilter = '';
    public string $smallGroupFilter = '';
    public string $locationFilter = '';
    public string $birthdateFrom = '';
    public string $birthdateTo = '';
    public string $minAge = '';
    public string $maxAge = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'smallGroupFilter' => ['except' => ''],
        'locationFilter' => ['except' => ''],
        'birthdateFrom' => ['except' => ''],
        'birthdateTo' => ['except' => ''],
        'minAge' => ['except' => ''],
        'maxAge' => ['except' => ''],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSmallGroupFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLocationFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBirthdateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingBirthdateTo(): void
    {
        $this->resetPage();
    }

    public function updatingMinAge(): void
    {
        $this->resetPage();
    }

    public function updatingMaxAge(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'roleFilter', 'statusFilter', 'smallGroupFilter', 'locationFilter', 'birthdateFrom', 'birthdateTo', 'minAge', 'maxAge']);
        $this->resetPage();
    }

    public function deleteUser(int $userId): void
    {
        $user = User::find($userId);

        if ($user && $user->id !== auth()->id()) {
            $user->delete();
            session()->flash('success', 'User deleted successfully.');
        }
    }

    protected function applyAgeFilter($query): void
    {
        $minAge = $this->minAge !== '' ? (int) $this->minAge : null;
        $maxAge = $this->maxAge !== '' ? (int) $this->maxAge : null;

        if ($minAge === null && $maxAge === null) {
            return;
        }

        $query->whereNotNull('birthdate');

        if ($minAge !== null) {
            $query->whereRaw($this->ageSqlExpression() . ' >= ?', [$minAge]);
        }

        if ($maxAge !== null) {
            $query->whereRaw($this->ageSqlExpression() . ' <= ?', [$maxAge]);
        }
    }

    protected function ageSqlExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "((CAST(strftime('%Y', 'now') AS INTEGER) - CAST(strftime('%Y', birthdate) AS INTEGER)) - (CASE WHEN strftime('%m-%d', 'now') < strftime('%m-%d', birthdate) THEN 1 ELSE 0 END))";
        }

        if ($driver === 'pgsql') {
            return "(EXTRACT(YEAR FROM AGE(CURRENT_DATE, birthdate)))";
        }

        return "(TIMESTAMPDIFF(YEAR, birthdate, CURDATE()))";
    }

    public function render()
    {
        $users = User::query()
            ->with(['smallGroups' => function ($query) {
                $query->where('small_group_members.status', 'active')
                    ->where('small_groups.status', 'active');
            }])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('address', 'like', "%{$this->search}%");
                });
            })
            ->when($this->roleFilter, fn ($query) => $query->where('role', $this->roleFilter))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->smallGroupFilter, function ($query) {
                $query->whereHas('smallGroups', function ($groupQuery) {
                    $groupQuery->where('small_groups.id', $this->smallGroupFilter)
                        ->where('small_group_members.status', 'active');
                });
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
            ->when($this->birthdateFrom, fn ($query) => $query->whereDate('birthdate', '>=', $this->birthdateFrom))
            ->when($this->birthdateTo, fn ($query) => $query->whereDate('birthdate', '<=', $this->birthdateTo))
            ->when($this->minAge !== '' || $this->maxAge !== '', function ($query) {
                $this->applyAgeFilter($query);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.user.index-user', [
            'users' => $users,
            'roles' => User::ROLES,
            'statuses' => User::STATUSES,
            'smallGroups' => \App\Models\SmallGroup::query()->active()->orderBy('name')->get(),
        ]);
    }
}
