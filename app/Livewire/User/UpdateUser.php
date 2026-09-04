<?php

namespace App\Livewire\User;

use App\Models\User;
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
    use WithFileUploads;

    public User $user;
    
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $gender = '';
    public ?string $birthdate = null;
    public string $phone = '';
    public string $address = '';
    public ?float $latitude = null;
    public ?float $longitude = null;
    public string $role = 'member';
    public string $status = 'active';
    public $profilePhoto;

    // PSGC Address Fields
    public string $regionCode = '';
    public string $provinceCode = '';
    public string $cityCode = '';
    public string $barangayCode = '';
    public string $streetAddress = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->gender = $user->gender ?? '';
        $this->birthdate = $user->birthdate?->format('Y-m-d');
        $this->phone = $user->phone ?? '';
        $this->address = $user->address ?? '';
        $this->regionCode = $user->region_code ?? '';
        $this->provinceCode = $user->province_code ?? '';
        $this->cityCode = $user->city_code ?? '';
        $this->barangayCode = $user->barangay_code ?? '';
        $this->streetAddress = $user->street_address ?? '';
        $this->latitude = $user->latitude;
        $this->longitude = $user->longitude;
        $this->role = $user->role;
        $this->status = $user->status;
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
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'role' => ['required', 'in:' . implode(',', User::ROLES)],
            'status' => ['required', 'in:' . implode(',', User::STATUSES)],
            'regionCode' => ['nullable', 'string'],
            'provinceCode' => ['nullable', 'string'],
            'cityCode' => ['nullable', 'string'],
            'barangayCode' => ['nullable', 'string'],
            'streetAddress' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'gender' => $validated['gender'],
            'birthdate' => $validated['birthdate'],
            'phone' => $validated['phone'] ?: null,
            'address' => $validated['address'] ?: null,
            'region_code' => $validated['regionCode'] ?: null,
            'province_code' => $validated['provinceCode'] ?: null,
            'city_code' => $validated['cityCode'] ?: null,
            'barangay_code' => $validated['barangayCode'] ?: null,
            'street_address' => $validated['streetAddress'] ?: null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'role' => $validated['role'],
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($this->profilePhoto) {
            if ($this->user->profile_photo_path) {
                Storage::disk('public')->delete($this->user->profile_photo_path);
            }
            $data['profile_photo_path'] = $this->profilePhoto->store('profile-photos', 'public');
        }

        $this->user->update($data);

        session()->flash('success', 'Member updated successfully.');

        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.user.update-user', [
            'roles' => User::ROLES,
            'statuses' => User::STATUSES,
            'genders' => [User::GENDER_MALE, User::GENDER_FEMALE],
        ]);
    }
}
