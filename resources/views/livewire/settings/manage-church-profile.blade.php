<x-settings-shell active="profile" title="Church profile" description="Keep the identity and regional preferences used throughout the system accurate.">
    <header class="settings-section-heading"><div><span class="event-eyebrow">Identity</span><h2>Church details</h2></div><p>Changes update shared branding after the page reloads.</p></header>
    <form wire:submit="save" class="event-form settings-form">
        <div class="settings-logo-field event-field event-field-wide">
            <div class="settings-logo-preview">@if($logo)<img src="{{ $logo->temporaryUrl() }}" alt="New logo preview">@elseif($existingLogo)<img src="{{ route('church-logo') }}" alt="Current church logo">@else<img src="{{ asset('images/true-vine-logo.png') }}" alt="Current church logo">@endif</div>
            <div><label for="church-logo">Church logo</label><p class="event-field-hint">PNG, JPG, or WebP up to 2 MB.</p><input id="church-logo" type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp">@error('logo')<p class="event-field-error">{{ $message }}</p>@enderror</div>
        </div>
        <div class="event-field"><label for="church-name">Church name</label><input id="church-name" wire:model="name">@error('name')<p class="event-field-error">{{ $message }}</p>@enderror</div>
        <div class="event-field"><label for="church-short-name">Short name</label><input id="church-short-name" wire:model="short_name">@error('short_name')<p class="event-field-error">{{ $message }}</p>@enderror</div>
        <div class="event-field event-field-wide"><label for="church-address">Address</label><textarea id="church-address" rows="3" wire:model="address"></textarea>@error('address')<p class="event-field-error">{{ $message }}</p>@enderror</div>
        <div class="event-field"><label for="church-phone">Phone</label><input id="church-phone" wire:model="phone"></div>
        <div class="event-field"><label for="church-email">Email</label><input id="church-email" type="email" wire:model="email">@error('email')<p class="event-field-error">{{ $message }}</p>@enderror</div>
        <div class="event-field event-field-wide"><label for="church-website">Website</label><input id="church-website" type="url" wire:model="website" placeholder="https://">@error('website')<p class="event-field-error">{{ $message }}</p>@enderror</div>
        <div class="event-field"><label for="church-timezone">Timezone</label><select id="church-timezone" wire:model="timezone">@foreach($timezones as $zone)<option value="{{ $zone }}">{{ $zone }}</option>@endforeach</select></div>
        <div class="event-field"><label for="church-locale">Locale</label><select id="church-locale" wire:model="locale"><option value="en">English</option><option value="fil">Filipino</option></select></div>
        <div class="event-field"><label for="church-date-format">Date format</label><select id="church-date-format" wire:model="date_format">@foreach($dateFormats as $format)<option value="{{ $format }}">{{ now()->format($format) }}</option>@endforeach</select></div>
        <div class="event-form-actions"><button class="event-button-primary" wire:loading.attr="disabled" wire:target="save,logo"><span wire:loading.remove wire:target="save">Save profile</span><span wire:loading wire:target="save">Saving…</span></button></div>
    </form>
</x-settings-shell>
