<div class="event-page access-page">
    <x-slot:headerTitle>Access Control</x-slot:headerTitle>
    <x-slot:headerSubtitle>True Vine World Harvest Church - Pangasinan</x-slot:headerSubtitle>

    <x-page-header title="Role permissions" subtitle="Choose exactly what each church role can see and do." />

    @if(session('success'))
        <div class="event-alert event-alert-success" role="status"><x-heroicon-o-check /><span>{{ session('success') }}</span></div>
    @endif

    <div class="access-layout">
        <aside class="access-role-rail" aria-labelledby="access-role-title">
            <div class="access-rail-heading">
                <span class="event-eyebrow">Permission profile</span>
                <h2 id="access-role-title">Choose a role</h2>
                <p>Each change applies to every active account assigned to that role.</p>
            </div>
            <nav class="access-role-list" aria-label="Roles">
                @foreach($roles as $role)
                    <button type="button" wire:click="$set('selectedRole', '{{ $role }}')" @class(['is-selected' => $selectedRole === $role])>
                        <span class="access-role-mark">{{ mb_strtoupper(mb_substr(str_replace('_', ' ', $role), 0, 1)) }}</span>
                        <span><strong>{{ ucwords(str_replace('_', ' ', $role)) }}</strong><small>{{ $rolePermissionCounts[$role] ?? 0 }} enabled</small></span>
                        <x-heroicon-o-chevron-right aria-hidden="true" />
                    </button>
                @endforeach
            </nav>
            <div class="access-admin-note"><x-heroicon-o-shield-check aria-hidden="true" /><p><strong>Admins have full access.</strong> Their permissions cannot be reduced from this screen.</p></div>
        </aside>

        <section class="access-permission-workspace" aria-labelledby="permission-workspace-title">
            <div class="access-workspace-heading">
                <div><span class="event-eyebrow">{{ ucwords(str_replace('_', ' ', $selectedRole)) }}</span><h2 id="permission-workspace-title">What can this role do?</h2></div>
                <span class="access-count"><strong>{{ count($selectedPermissions) }}</strong> enabled</span>
            </div>
            <p class="access-workspace-help">Turn on the actions this role needs. Required view access is added automatically when an action depends on it.</p>

            <form wire:submit="save">
                <div class="access-permission-grid">
                    @foreach($permissionGroups as $group => $permissions)
                        <fieldset class="access-module">
                            <legend><span>{{ $group }}</span><small>{{ collect($permissions)->keys()->filter(fn ($key) => in_array($key, $selectedPermissions, true))->count() }}/{{ count($permissions) }}</small></legend>
                            @foreach($permissions as $permission => $label)
                                @php $checked = in_array($permission, $selectedPermissions, true); $restricted = $this->isRestrictedPermission($permission); @endphp
                                <label @class(['access-permission', 'is-enabled' => $checked, 'is-restricted' => $restricted]) wire:key="{{ $selectedRole }}-{{ $permission }}">
                                    <input type="checkbox" @checked($checked) @disabled($restricted) wire:change="togglePermission('{{ $permission }}')">
                                    <span class="access-checkbox" aria-hidden="true"></span>
                                    <span><strong>{{ $label }}</strong><small>{{ $restricted ? 'Restricted to protect unassigned member data' : $permission }}</small></span>
                                </label>
                            @endforeach
                        </fieldset>
                    @endforeach
                </div>
                <div class="access-savebar">
                    <p><strong>{{ count($selectedPermissions) }}</strong> permissions enabled for {{ ucwords(str_replace('_', ' ', $selectedRole)) }}.</p>
                    <button type="submit" class="event-button-primary" wire:loading.attr="disabled" wire:target="save">
                        <x-heroicon-o-check /> <span wire:loading.remove wire:target="save">Save permissions</span><span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
