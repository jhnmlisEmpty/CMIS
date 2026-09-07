<div class="event-page member-page">
    <x-slot:headerTitle>My Profile</x-slot:headerTitle>

    <x-page-header title="Edit my profile" subtitle="Keep your personal information and contact details up to date." :backRoute="route('profile')" backLabel="My profile" />

    <form wire:submit="save" class="member-editor-layout">
        <aside class="event-editor-intro member-editor-intro" aria-label="Your profile summary">
            <span class="event-eyebrow">Your profile</span>
            @if($user->profile_photo_path)<img src="{{ route('profile-photo', ['filename' => basename($user->profile_photo_path)]) }}" alt="{{ $user->name }}" class="member-editor-avatar member-photo">@else<span class="member-editor-avatar" aria-hidden="true">{{ collect(explode(' ', $user->name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span>@endif
            <h2>{{ $user->name }}</h2>
            <p>These changes update the information church leaders use to contact and care for you.</p>
            <dl class="event-editor-summary"><div><dt>Joined</dt><dd>{{ $user->created_at?->format('M j, Y') ?? '—' }}</dd></div><div><dt>Member ID</dt><dd>#{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</dd></div></dl>
        </aside>

        <div class="member-form-stack">
            <section class="event-form-panel" aria-labelledby="profile-info-title">
                <div class="event-section-heading"><div><span class="event-section-index">01</span><h2 id="profile-info-title">Personal information</h2></div><p><span aria-hidden="true">*</span> Required fields</p></div>
                <div class="event-form member-fields">
                    <div class="event-field event-field-wide"><label for="name">Full name <span aria-hidden="true">*</span></label><p class="event-field-hint">Use the name your church record should display.</p><input type="text" id="name" wire:model="name" autocomplete="name" class="@error('name') is-invalid @enderror">@error('name')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="event-field event-field-wide"><label for="profilePhoto">Profile picture</label><p class="event-field-hint">Optional JPG, PNG, or WEBP image up to 5 MB.</p><input type="file" id="profilePhoto" wire:model="profilePhoto" accept="image/jpeg,image/png,image/webp" class="@error('profilePhoto') is-invalid @enderror">@error('profilePhoto')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror<div wire:loading wire:target="profilePhoto" class="event-field-hint">Uploading…</div></div>
                    <div class="event-field"><label for="email">Email address <span aria-hidden="true">*</span></label><p class="event-field-hint">Used for contact and account identification.</p><input type="email" id="email" wire:model="email" autocomplete="email" class="@error('email') is-invalid @enderror">@error('email')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="event-field"><label for="phone">Phone number</label><p class="event-field-hint">Include the country code when possible.</p><input type="tel" id="phone" wire:model="phone" autocomplete="tel" placeholder="+63 9XX XXX XXXX" class="@error('phone') is-invalid @enderror">@error('phone')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="event-field"><label for="gender">Gender <span aria-hidden="true">*</span></label><p class="event-field-hint">Select the gender in your member record.</p><select id="gender" wire:model="gender" class="@error('gender') is-invalid @enderror"><option value="">Select gender</option>@foreach($genders as $gender)<option value="{{ $gender }}">{{ ucfirst($gender) }}</option>@endforeach</select>@error('gender')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="event-field"><label for="birthdate">Birthdate</label><p class="event-field-hint">This is also used when signing in.</p><input type="date" id="birthdate" wire:model="birthdate" autocomplete="bday" max="{{ today()->subDay()->format('Y-m-d') }}" class="@error('birthdate') is-invalid @enderror">@error('birthdate')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                </div>
            </section>

            <section class="event-form-panel member-location-panel" aria-labelledby="profile-location-title">
                <div class="event-section-heading"><div><span class="event-section-index">02</span><h2 id="profile-location-title">Address and location</h2></div><p>Optional</p></div>
                <div class="member-location-body"><p class="member-section-intro">Choose your address and confirm the map position. This helps the church understand where members are located.</p><livewire:components.address-map-picker :region-code="$regionCode" :province-code="$provinceCode" :city-code="$cityCode" :barangay-code="$barangayCode" :street-address="$streetAddress" :latitude="$latitude" :longitude="$longitude" /></div>
            </section>

            <section class="event-form-panel" aria-labelledby="profile-security-title">
                <div class="event-section-heading"><div><span class="event-section-index">03</span><h2 id="profile-security-title">Sign-in security</h2></div><p>Optional</p></div>
                <div class="event-form member-fields"><div class="event-field"><label for="password">New password</label><p class="event-field-hint">Leave blank to keep your current password.</p><input type="password" id="password" wire:model="password" autocomplete="new-password" class="@error('password') is-invalid @enderror">@error('password')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div><div class="event-field"><label for="password_confirmation">Confirm new password</label><p class="event-field-hint">Required only when changing the password.</p><input type="password" id="password_confirmation" wire:model="password_confirmation" autocomplete="new-password"></div></div>
                <div class="event-form-actions member-form-actions"><a href="{{ route('profile') }}" class="event-button-secondary" wire:navigate>Cancel</a><button type="submit" class="event-button-primary" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Save my profile</span><span wire:loading wire:target="save">Saving…</span><x-heroicon-o-check wire:loading.remove wire:target="save" /></button></div>
            </section>
        </div>
    </form>
</div>
