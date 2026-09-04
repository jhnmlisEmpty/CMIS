<div class="event-page member-page">
    <x-slot:headerTitle>Edit Member</x-slot:headerTitle>

    <x-page-header title="Edit member" :subtitle="'Keep ' . $user->name . '’s profile and account access accurate.'" :backRoute="route('users.show', $user)" backLabel="Member details" />

    <form wire:submit="save" class="member-editor-layout">
        <aside class="event-editor-intro member-editor-intro" aria-label="Current member summary">
            <span class="event-eyebrow">Currently editing</span>
            @if($user->profile_photo_path)<img src="{{ route('profile-photo', ['filename' => basename($user->profile_photo_path)]) }}" alt="{{ $user->name }}" class="member-editor-avatar member-photo">@else<span class="member-editor-avatar">{{ collect(explode(' ', $user->name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span>@endif
            <h2>{{ $user->name }}</h2>
            <p>Profile changes are reflected in attendance, small groups, and member location tools.</p>
            <dl class="event-editor-summary"><div><dt>Joined</dt><dd>{{ $user->created_at?->format('M j, Y') ?? '—' }}</dd></div><div><dt>Member ID</dt><dd>#{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</dd></div></dl>
        </aside>

        <div class="member-form-stack">
            <section class="event-form-panel" aria-labelledby="member-identity-title">
                <div class="event-section-heading"><div><span class="event-section-index">01</span><h2 id="member-identity-title">Personal information</h2></div><p><span aria-hidden="true">*</span> Required fields</p></div>
                <div class="event-form member-fields">
                    <div class="event-field event-field-wide"><label for="name">Full name <span aria-hidden="true">*</span></label><p class="event-field-hint">Enter the member’s complete name.</p><input type="text" id="name" wire:model="name" placeholder="e.g. Maria Santos" autocomplete="name" class="@error('name') is-invalid @enderror">@error('name')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="event-field event-field-wide"><label for="profilePhoto">Profile picture</label><p class="event-field-hint">Upload a new JPG, PNG, or WEBP image up to 5 MB.</p><input type="file" id="profilePhoto" wire:model="profilePhoto" accept="image/jpeg,image/png,image/webp" class="@error('profilePhoto') is-invalid @enderror">@error('profilePhoto')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror<div wire:loading wire:target="profilePhoto" class="event-field-hint">Uploading preview…</div>@if($profilePhoto)<img src="{{ $profilePhoto->temporaryUrl() }}" alt="New profile picture preview" class="member-photo-preview">@elseif($user->profile_photo_path)<img src="{{ route('profile-photo', ['filename' => basename($user->profile_photo_path)]) }}" alt="{{ $user->name }}" class="member-photo-preview">@endif</div>
                    <div class="event-field"><label for="email">Email address <span aria-hidden="true">*</span></label><p class="event-field-hint">Used for contact and account identification.</p><input type="email" id="email" wire:model="email" placeholder="name@example.com" autocomplete="email" class="@error('email') is-invalid @enderror">@error('email')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="event-field"><label for="phone">Phone number</label><p class="event-field-hint">Include the country code when possible.</p><input type="tel" id="phone" wire:model="phone" placeholder="+63 9XX XXX XXXX" autocomplete="tel" class="@error('phone') is-invalid @enderror">@error('phone')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="event-field"><label for="gender">Gender <span aria-hidden="true">*</span></label><p class="event-field-hint">Select the member’s gender.</p><select id="gender" wire:model="gender" class="@error('gender') is-invalid @enderror"><option value="">Select gender</option>@foreach($genders as $gender)<option value="{{ $gender }}">{{ ucfirst($gender) }}</option>@endforeach</select>@error('gender')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="event-field"><label for="birthdate">Birthdate</label><p class="event-field-hint">Used for member records and sign-in.</p><input type="date" id="birthdate" wire:model="birthdate" autocomplete="bday" class="@error('birthdate') is-invalid @enderror">@error('birthdate')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                </div>
            </section>

            <section class="event-form-panel member-location-panel" aria-labelledby="member-location-title">
                <div class="event-section-heading"><div><span class="event-section-index">02</span><h2 id="member-location-title">Address and location</h2></div><p>Optional</p></div>
                <div class="member-location-body">
                    @if($address && !$regionCode)
                        <div class="member-current-address"><span><x-heroicon-o-map-pin /></span><div><small>Current address</small><strong>{{ $address }}</strong>@if($latitude && $longitude)<code>{{ number_format($latitude, 6) }}, {{ number_format($longitude, 6) }}</code>@endif</div></div>
                    @endif
                    <p class="member-section-intro">Choose an address and confirm the map position. Selecting a new location will replace the current address.</p>
                    <livewire:components.address-map-picker :region-code="$regionCode" :province-code="$provinceCode" :city-code="$cityCode" :barangay-code="$barangayCode" :street-address="$streetAddress" :latitude="$latitude" :longitude="$longitude" />
                </div>
            </section>

            <section class="event-form-panel" aria-labelledby="member-account-title">
                <div class="event-section-heading"><div><span class="event-section-index">03</span><h2 id="member-account-title">Account access</h2></div><p><span aria-hidden="true">*</span> Required fields</p></div>
                <div class="event-form member-fields">
                    <div class="event-field"><label for="password">New password</label><p class="event-field-hint">Leave blank to keep the current password.</p><input type="password" id="password" wire:model="password" placeholder="Enter a new password" autocomplete="new-password" class="@error('password') is-invalid @enderror">@error('password')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="event-field"><label for="password_confirmation">Confirm new password</label><p class="event-field-hint">Required only when changing the password.</p><input type="password" id="password_confirmation" wire:model="password_confirmation" placeholder="Confirm new password" autocomplete="new-password"></div>
                    <div class="event-field"><label for="role">Role <span aria-hidden="true">*</span></label><p class="event-field-hint">Controls access and responsibilities.</p><select id="role" wire:model="role" class="@error('role') is-invalid @enderror">@foreach($roles as $role)<option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}</option>@endforeach</select>@error('role')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="event-field"><label for="status">Status <span aria-hidden="true">*</span></label><p class="event-field-hint">Only active members can use the system.</p><select id="status" wire:model="status" class="@error('status') is-invalid @enderror">@foreach($statuses as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select>@error('status')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                </div>
                <div class="event-form-actions member-form-actions"><a href="{{ route('users.show', $user) }}" class="event-button-secondary" wire:navigate>Cancel</a><button type="submit" class="event-button-primary" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Save changes</span><span wire:loading wire:target="save">Saving…</span><x-heroicon-o-chevron-right wire:loading.remove wire:target="save" /></button></div>
            </section>
        </div>
    </form>
</div>
