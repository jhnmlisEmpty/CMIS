<?php

namespace App\Livewire\Settings;

use App\Models\ChurchSetting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Church Profile | Settings')]
class ManageChurchProfile extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $short_name = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $website = '';
    public string $timezone = 'Asia/Manila';
    public string $locale = 'en';
    public string $date_format = 'M j, Y';
    public $logo;
    public ?string $existingLogo = null;

    public function mount(): void
    {
        Gate::authorize('access-control.manage');
        $setting = ChurchSetting::current();
        foreach (['name', 'short_name', 'address', 'phone', 'email', 'website', 'timezone', 'locale', 'date_format'] as $field) {
            $this->{$field} = (string) ($setting->{$field} ?? '');
        }
        $this->existingLogo = $setting->logo_path;
    }

    public function save(): void
    {
        Gate::authorize('access-control.manage');
        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'short_name' => ['required', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'locale' => ['required', Rule::in(ChurchSetting::LOCALES)],
            'date_format' => ['required', Rule::in(ChurchSetting::DATE_FORMATS)],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $setting = ChurchSetting::current();
        $oldLogo = $setting->logo_path;
        unset($data['logo']);
        if ($this->logo) {
            $data['logo_path'] = $this->logo->store('church-branding', 'public');
        }
        $setting->update($data);

        if ($this->logo && $oldLogo && $oldLogo !== $setting->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }
        $this->existingLogo = $setting->logo_path;
        $this->reset('logo');
        session()->flash('success', 'Church profile updated.');
    }

    public function render()
    {
        return view('livewire.settings.manage-church-profile', [
            'timezones' => timezone_identifiers_list(),
            'dateFormats' => ChurchSetting::DATE_FORMATS,
        ]);
    }
}
