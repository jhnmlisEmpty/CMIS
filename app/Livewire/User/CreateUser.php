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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Add Member')]
class CreateUser extends Component
{
    use ManagesTags;
    use WithFileUploads;

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

    protected function tagType(): string
    {
        return Tag::TYPE_MEMBER;
    }

    public function mount(): void
    {
        $defaultRoleId = Role::defaultForNewUsers()?->id;
        $this->role_ids = $defaultRoleId ? [(int) $defaultRoleId] : [];
    }

    public function updatedRole(): void
    {
        $roleId = Role::query()->where('slug', $this->role)->value('id');
        if ($roleId) {
            $this->role_ids = [(int) $roleId];
        }
    }

    // PSGC Address Fields
    public string $regionCode = '';

    public string $provinceCode = '';

    public string $cityCode = '';

    public string $barangayCode = '';

    public string $streetAddress = '';

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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
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
        Gate::authorize('users.create');
        $validated = $this->validate();

        $tagIds = $validated['tag_ids'];
        $pendingTags = $validated['pending_tags'];

        $canAssignRoles = auth()->user()->hasAllScope('users.assign_roles');
        abort_if($canAssignRoles && ! $access->canAssignRoles(auth()->user(), $validated['role_ids']), 403);
        $createdUser = DB::transaction(function () use ($validated, $tagIds, $pendingTags, $canAssignRoles): User {
            $user = User::create([
                'uuid' => Str::uuid(),
                'name' => $validated['name'],
                'profile_photo_path' => $this->profilePhoto?->store('profile-photos', 'local'),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'gender' => $validated['gender'],
                'birthdate' => $validated['birthdate'],
                'phone' => $validated['phone'],
                'social_media_url' => $validated['socialMediaUrl'] ?: null,
                'address' => $validated['address'],
                'region_code' => $validated['regionCode'] ?: null,
                'province_code' => $validated['provinceCode'] ?: null,
                'city_code' => $validated['cityCode'] ?: null,
                'barangay_code' => $validated['barangayCode'] ?: null,
                'street_address' => $validated['streetAddress'] ?: null,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'status' => $validated['status'],
            ]);

            if ($canAssignRoles) {
                $user->roles()->sync(array_values(array_unique($validated['role_ids'])));
            }

            $this->syncTags($user, $tagIds, $pendingTags);

            return $user;
        });

        $audit->log('updated', 'members', 'Roles assigned to '.$createdUser->name, $createdUser, [], [
            'roles' => $createdUser->roles()->pluck('slug')->all(),
        ]);

        session()->flash('success', 'Member created successfully.');

        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        $canAssignRoles = auth()->user()->hasAllScope('users.assign_roles');

        return view('livewire.user.create-user', [
            'roles' => Role::query()
                ->when(! auth()->user()->isAdmin(), fn ($query) => $query->where('slug', '!=', Role::ADMIN))
                ->orderByDesc('is_system')->orderBy('id')->get(),
            'canAssignRoles' => $canAssignRoles,
            'statuses' => User::STATUSES,
            'genders' => [User::GENDER_MALE, User::GENDER_FEMALE],
            ...$this->tagViewData(),
        ]);
    }
}
