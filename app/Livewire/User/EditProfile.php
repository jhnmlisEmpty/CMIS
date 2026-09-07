<?php

namespace App\Livewire\User;

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
#[Title('My Profile')]
class EditProfile extends Component
{
    use WithFileUploads;

    public $user;
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
    public $profilePhoto;

    public string $regionCode = '';
    public string $provinceCode = '';
    public string $cityCode = '';
    public string $barangayCode = '';
    public string $streetAddress = '';

    public function mount(): void
    {
        $this->user = auth()->user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->gender = $this->user->gender ?? '';
        $this->birthdate = $this->user->birthdate?->format('Y-m-d');
        $this->phone = $this->user->phone ?? '';
        $this->address = $this->user->address ?? '';
        $this->regionCode = $this->user->region_code ?? '';
        $this->provinceCode = $this->user->province_code ?? '';
        $this->cityCode = $this->user->city_code ?? '';
        $this->barangayCode = $this->user->barangay_code ?? '';
        $this->streetAddress = $this->user->street_address ?? '';
        $this->latitude = $this->user->latitude;
        $this->longitude = $this->user->longitude;
    }

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

    protected function rules(): array
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
        ];

        if ($validated['password'] ?? null) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($this->profilePhoto) {
            if ($this->user->profile_photo_path) {
                Storage::disk('public')->delete($this->user->profile_photo_path);
            }
            $data['profile_photo_path'] = $this->profilePhoto->store('profile-photos', 'public');
        }

        $this->user->update($data);
        session()->flash('success', 'Your profile was updated successfully.');
        $this->redirect(route('profile'), navigate: true);
    }

    public function render()
    {
        return view('livewire.user.edit-profile', [
            'genders' => ['male', 'female'],
        ]);
    }
}
