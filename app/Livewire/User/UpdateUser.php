<?php

namespace App\Livewire\User;

use App\Livewire\Concerns\ManagesTags;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Services\AccessManager;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Edit Member')]
class UpdateUser extends Component
{
    use ManagesTags;
    use WithFileUploads;

    public User $user;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $gender = '';

    public ?string $birthdate = null;

    public string $phone = '';

    public string $socialMediaUrl = '';

    public string $address = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public array $role_ids = [];

    /** @deprecated Compatibility input for older clients; use role_ids. */
    public string $role = Role::MEMBER;

    public string $status = 'active';

    public $profilePhoto;

    // PSGC Address Fields
    public string $regionCode = '';

    public string $provinceCode = '';

    public string $cityCode = '';

    public string $barangayCode = '';

    public string $streetAddress = '';

    public bool $leaderStatusLocked = false;

    public array $ledSmallGroups = [];

    protected function tagType(): string
    {
        return Tag::TYPE_MEMBER;
    }

    public function mount(User $user): void
    {
        Gate::authorize('users.update');
        abort_unless(auth()->user()->canAccessMember($user, 'users.update'), 403);
        abort_if($user->isAdmin() && ! auth()->user()->isAdmin(), 403);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->gender = $user->gender ?? '';
        $this->birthdate = $user->birthdate?->format('Y-m-d');
        $this->phone = $user->phone ?? '';
        $this->socialMediaUrl = $user->social_media_url ?? '';
        $this->address = $user->address ?? '';
        $this->regionCode = $user->region_code ?? '';
        $this->provinceCode = $user->province_code ?? '';
        $this->cityCode = $user->city_code ?? '';
        $this->barangayCode = $user->barangay_code ?? '';
        $this->streetAddress = $user->street_address ?? '';
        $this->latitude = $user->latitude;
        $this->longitude = $user->longitude;
        $this->role_ids = $user->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->all();
        $this->role = $user->role ?? Role::MEMBER;
        $this->status = $user->status;
        $this->tag_ids = $user->tags()->pluck('tags.id')->all();
        $this->ledSmallGroups = $user->ledSmallGroups()
            ->orderBy('name')
            ->get(['small_groups.id', 'small_groups.name'])
            ->map(fn ($group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'can_update' => auth()->user()->canAccessSmallGroup($group, 'small_groups.update'),
            ])
            ->all();
        $this->leaderStatusLocked = count($this->ledSmallGroups) > 0;
    }

    public function updatedRole(): void
    {
        $roleId = Role::query()->where('slug', $this->role)->value('id');
        if ($roleId) {
            $this->role_ids = [(int) $roleId];
        }
    }

    /**
     * Handle location selection from AddressMapPicker component
     */
    #[On('location-selected')]
    public function handleLocationSelected(
        ?float $latitude,
        ?float $longitude,
        string $address,
        string $regionCode,
        string $provinceCode,
        string $cityCode,
        string $barangayCode,
        string $streetAddress
    ): void {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->address = $address;
        $this->regionCode = $regionCode;
        $this->provinceCode = $provinceCode;
        $this->cityCode = $cityCode;
        $this->barangayCode = $barangayCode;
        $this->streetAddress = $streetAddress;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'profilePhoto' => ['nullable', 'image', 'max:5120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user->id)],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
            'gender' => ['required', 'in:male,female'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:20'],
            'socialMediaUrl' => ['nullable', 'url:http,https', 'max:2048'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')],
            'status' => ['required', 'in:'.implode(',', User::STATUSES)],
            'regionCode' => ['nullable', 'string'],
            'provinceCode' => ['nullable', 'string'],
            'cityCode' => ['nullable', 'string'],
            'barangayCode' => ['nullable', 'string'],
            'streetAddress' => ['nullable', 'string', 'max:255'],
            ...$this->tagRules(),
        ];
    }

    public function save(AuditLogger $audit, AccessManager $access): void
    {
        Gate::authorize('users.update');
        abort_unless(auth()->user()->canAccessMember($this->user, 'users.update'), 403);
        abort_if($this->user->isAdmin() && ! auth()->user()->isAdmin(), 403);
        $validated = $this->validate();
        $tagIds = $validated['tag_ids'];
        $pendingTags = $validated['pending_tags'];
        $previousRoles = $this->user->roles()->pluck('slug')->sort()->values()->all();
        $canAssignRoles = Gate::allows('users.assign_roles')
            && $access->canAssignRoles(auth()->user(), $validated['role_ids'], $this->user);
        abort_if(Gate::allows('users.assign_roles') && ! $canAssignRoles
            && $this->roleSelectionChanges($validated['role_ids']), 403);

        $deactivatesGroupLeader = $validated['status'] !== User::STATUS_ACTIVE
            && $this->user->ledSmallGroups()->exists();
        abort_if($deactivatesGroupLeader, 422, 'Remove this leader from their small groups before deactivating the account.');

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'gender' => $validated['gender'],
            'birthdate' => $validated['birthdate'],
            'phone' => $validated['phone'] ?: null,
            'social_media_url' => $validated['socialMediaUrl'] ?: null,
            'address' => $validated['address'] ?: null,
            'region_code' => $validated['regionCode'] ?: null,
            'province_code' => $validated['provinceCode'] ?: null,
            'city_code' => $validated['cityCode'] ?: null,
            'barangay_code' => $validated['barangayCode'] ?: null,
            'street_address' => $validated['streetAddress'] ?: null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => $validated['status'],
        ];

        if ($canAssignRoles) {
            $adminRoleId = Role::query()->where('slug', Role::ADMIN)->value('id');
            $removesFinalActiveAdmin = $this->user->isAdmin()
                && $this->user->isActive()
                && (! in_array((int) $adminRoleId, array_map('intval', $validated['role_ids']), true) || $validated['status'] !== User::STATUS_ACTIVE)
                && User::where('status', User::STATUS_ACTIVE)->whereHas('roles', fn ($query) => $query->where('slug', Role::ADMIN))->count() <= 1;
            abort_if($removesFinalActiveAdmin, 422, 'The final active administrator cannot be demoted or deactivated.');
        }

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($this->profilePhoto) {
            if ($this->user->profile_photo_path) {
                Storage::disk('local')->delete($this->user->profile_photo_path);
            }
            $data['profile_photo_path'] = $this->profilePhoto->store('profile-photos', 'local');
        }

        DB::transaction(function () use ($data, $tagIds, $pendingTags, $validated, $canAssignRoles, $access): void {
            $this->user->update($data);
            if ($canAssignRoles) {
                $this->user->roles()->sync(array_values(array_unique($validated['role_ids'])));
                $this->user->unsetRelation('roles');
                $access->forget($this->user);
            }
            $this->syncTags($this->user, $tagIds, $pendingTags);
        });

        $newRoles = $this->user->roles()->pluck('slug')->sort()->values()->all();
        if ($previousRoles !== $newRoles) {
            $audit->log('updated', 'members', 'Roles updated for '.$this->user->name, $this->user, ['roles' => $previousRoles], ['roles' => $newRoles]);
        }

        session()->flash('success', 'Member updated successfully.');

        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        $canAssignRoles = Gate::allows('users.assign_roles')
            && app(AccessManager::class)->canAssignRoles(
                auth()->user(),
                $this->role_ids,
                $this->user,
            );

        return view('livewire.user.update-user', [
            'roles' => Role::query()
                ->when(! auth()->user()->isAdmin(), fn ($query) => $query->where('slug', '!=', Role::ADMIN))
                ->orderByDesc('is_system')->orderBy('id')->get(),
            'canAssignRoles' => $canAssignRoles,
            'statuses' => User::STATUSES,
            'genders' => [User::GENDER_MALE, User::GENDER_FEMALE],
            ...$this->tagViewData(),
        ]);
    }

    /** @param list<int|string> $roleIds */
    private function roleSelectionChanges(array $roleIds): bool
    {
        $current = $this->user->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $requested = collect($roleIds)->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        return $current !== $requested;
    }
}
