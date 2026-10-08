<x-settings-shell active="access" title="Roles and permissions" description="Create church roles, configure their access, and assign more than one role to a member.">
    <div class="access-layout">
        <aside class="access-role-rail" aria-labelledby="access-role-title">
            <div class="access-rail-heading">
                <div class="access-rail-title">
                    <div><span class="event-eyebrow">Permission profiles</span><h2 id="access-role-title">Choose a role</h2></div>
                    <button type="button" class="access-add-role" wire:click="$toggle('showCreateRole')" aria-label="Create role"><x-heroicon-o-plus /></button>
                </div>
                <p>Every role can be renamed or removed when it is no longer assigned or referenced.</p>
            </div>

            @if($showCreateRole)
                <form wire:submit="createRole" class="access-role-editor">
                    <label for="new-role-name">New role name</label>
                    <div><input id="new-role-name" wire:model="newRoleName" maxlength="80" autofocus placeholder="e.g. Worship coordinator"><button class="event-button-primary">Create</button></div>
                    @error('newRoleName')<p class="event-field-error">{{ $message }}</p>@enderror
                </form>
            @endif

            <nav class="access-role-list" aria-label="Roles">
                @foreach($roles as $role)
                    <button type="button" wire:click="requestRoleChange({{ $role->id }})" @disabled($selectedRoleId === $role->id) wire:loading.attr="disabled" @class(['is-selected' => $selectedRoleId === $role->id])>
                        <span class="access-role-mark">{{ mb_strtoupper(mb_substr($role->name, 0, 1)) }}</span>
                        <span><strong>{{ $role->name }}</strong><small><span>{{ $role->slug === \App\Models\Role::ADMIN ? 'Full access' : (($rolePermissionCounts[$role->id] ?? 0).' permissions') }}</span><span>{{ $role->users_count }} {{ Str::plural('member', $role->users_count) }}</span><span>{{ $role->is_system ? 'Built-in' : 'Custom' }}</span></small></span>
                        <x-heroicon-o-chevron-right aria-hidden="true" />
                    </button>
                @endforeach
            </nav>
            <div class="access-admin-note"><x-heroicon-o-shield-check aria-hidden="true" /><p><strong>Deletion stays safe.</strong> Remove a role from members and attendance rules before deleting it.</p></div>
        </aside>

        @if($selectedRoleRecord)
            <section class="access-permission-workspace" aria-labelledby="permission-workspace-title">
                <div class="access-workspace-heading">
                    <div><span class="event-eyebrow">{{ $selectedRoleRecord->is_system ? 'Built-in role' : 'Custom role' }}</span><h2 id="permission-workspace-title">{{ $selectedRoleRecord->name }}</h2></div>
                    <div class="access-workspace-meta">
                        <span class="access-count">@if($selectedRoleRecord->slug === \App\Models\Role::ADMIN)<strong>Full</strong> access @else<strong>{{ count($selectedPermissions) }}</strong> enabled @endif</span>
                        <div class="settings-row-actions"><button type="button" wire:click="beginRename">Rename</button><button type="button" class="is-danger" wire:click="deleteRole" @disabled(in_array($selectedRoleRecord->slug, [\App\Models\Role::ADMIN, \App\Models\Role::MEMBER], true)) wire:confirm="Delete this role? It will be removed from assigned members, and members with no remaining role will receive the Member role.">Delete</button></div>
                    </div>
                </div>
                <p class="access-workspace-help">Choose record access once on each core data permission. That scope applies to every enabled feature that uses the same data. Associated access includes the member's own profile and records belonging to small groups they lead.</p>

                @error('permissions')
                    <div class="event-alert event-alert-error" role="alert"><span>{{ $message }}</span></div>
                @enderror
                @if($permissionNotices && $permissionsDirty)
                    <div class="access-change-preview" role="status" aria-live="polite">
                        <div><strong>Saving will also make these changes</strong><p>Your current selections remain visible until you review and confirm.</p><ul>
                            @foreach($permissionNotices as $notice)<li>{{ $notice }}</li>@endforeach
                        </ul></div>
                    </div>
                @endif

                @if($showRenameRole)
                    <form wire:submit="renameRole" class="access-role-editor access-role-editor-inline">
                        <label for="editing-role-name">Role name</label>
                        <div><input id="editing-role-name" wire:model="editingRoleName" maxlength="80" autofocus><button type="button" class="event-button-secondary" wire:click="$set('showRenameRole', false)">Cancel</button><button class="event-button-primary">Save</button></div>
                        @error('editingRoleName')<p class="event-field-error">{{ $message }}</p>@enderror
                    </form>
                @endif

                @if($selectedRoleRecord->slug === \App\Models\Role::ADMIN)
                    <div class="access-admin-permissions"><x-heroicon-o-key /><div><strong>Administrators always have full access</strong><p>The role name can be changed, but its permission set stays unrestricted. Delete becomes available only after every member has been removed from this role.</p></div></div>
                @else
                <form wire:submit="save">
                    <div class="access-permission-grid">
                        @foreach($permissionGroups as $group => $permissions)
                            <fieldset class="access-module">
                                <legend><span>{{ $group }}</span><small>{{ collect($permissions)->keys()->filter(fn ($key) => in_array($key, $selectedPermissions, true))->count() }}/{{ count($permissions) }}</small></legend>
                                @foreach($permissions as $permission => $label)
                                    @php
                                        $checked = in_array($permission, $selectedPermissions, true);
                                        $scopeRoot = \App\Support\PermissionRegistry::scopeRootFor($permission);
                                        $scopeRootLabel = $scopeRoot ? (collect($permissionGroups)->collapse()[$scopeRoot] ?? $scopeRoot) : null;
                                    @endphp
                                    @if(in_array($permission, $scopeablePermissions, true))
                                        <label @class(['access-permission', 'access-permission-scoped', 'is-enabled' => $checked]) wire:key="{{ $selectedRoleId }}-{{ $permission }}">
                                            <span><strong>{{ $label }}</strong><small>Core data scope · {{ $permission }}</small>@if($dependencies[$permission] ?? [])<small>Requires {{ collect($dependencies[$permission])->map(fn($key) => collect($permissionGroups)->collapse()[$key] ?? $key)->join(', ') }}</small>@endif</span>
                                            <select wire:model.change="permissionScopeInputs.{{ str_replace('.', '__', $permission) }}" aria-label="Access scope for {{ $label }}">
                                                <option value="none">No access</option>
                                                <option value="associated">Associated only</option>
                                                <option value="all">All records</option>
                                            </select>
                                        </label>
                                    @else
                                        <label @class(['access-permission', 'is-enabled' => $checked]) wire:key="{{ $selectedRoleId }}-{{ $permission }}">
                                            <input type="checkbox" @checked($checked) wire:change="togglePermission('{{ $permission }}')">
                                            <span class="access-checkbox" aria-hidden="true"></span>
                                            <span><strong>{{ $label }}</strong><small>{{ $permission }}</small>@if($scopeRootLabel)<small>Uses {{ $scopeRootLabel }} record scope</small>@endif @if($dependencies[$permission] ?? [])<small>Requires {{ collect($dependencies[$permission])->map(fn($key) => collect($permissionGroups)->collapse()[$key] ?? $key)->join(', ') }}</small>@endif</span>
                                        </label>
                                    @endif
                                @endforeach
                            </fieldset>
                        @endforeach
                    </div>
                    <div class="access-savebar">
                        <p><strong>{{ count($selectedPermissions) }}</strong> permissions enabled for {{ $selectedRoleRecord->name }}. @if($permissionsDirty)<strong role="status">Unsaved changes</strong>@endif</p>
                        <button type="submit" class="event-button-primary" wire:loading.attr="disabled" wire:target="save,confirmPermissionSave">
                            <x-heroicon-o-check /> <span wire:loading.remove wire:target="save,confirmPermissionSave">{{ $permissionAdjustments ? 'Review and save' : 'Save permissions' }}</span><span wire:loading wire:target="save,confirmPermissionSave">Saving...</span>
                        </button>
                    </div>
                </form>
                @endif
            </section>
        @endif
    </div>

    @if($showPermissionConfirmation)
        <div class="access-dialog-backdrop" role="presentation">
            <section class="access-dialog" role="dialog" aria-modal="true" aria-labelledby="permission-confirm-title">
                <div class="access-dialog-icon"><x-heroicon-o-shield-check /></div>
                <h2 id="permission-confirm-title">Confirm required access</h2>
                <p>These dependencies are needed for the permissions you selected. Nothing changes until you confirm.</p>
                <ul class="access-dialog-changes">@foreach($permissionNotices as $notice)<li>{{ $notice }}</li>@endforeach</ul>
                <div class="access-dialog-actions">
                    <button type="button" class="event-button-secondary" wire:click="cancelPermissionConfirmation">Keep editing</button>
                    <button type="button" class="event-button-primary" wire:click="confirmPermissionSave" wire:loading.attr="disabled" wire:target="confirmPermissionSave">Confirm and save</button>
                </div>
            </section>
        </div>
    @endif

    @if($showUnsavedPrompt)
        <div class="access-dialog-backdrop" role="presentation">
            <section class="access-dialog" role="dialog" aria-modal="true" aria-labelledby="unsaved-role-title">
                <div class="access-dialog-icon"><x-heroicon-o-pencil-square /></div>
                <h2 id="unsaved-role-title">Save changes before switching?</h2>
                <p>This role has permission changes that have not been saved.</p>
                <div class="access-dialog-actions access-dialog-actions-three">
                    <button type="button" class="event-button-secondary" wire:click="stayOnRole">Stay here</button>
                    <button type="button" class="event-button-secondary is-danger" wire:click="discardAndSwitchRole">Discard changes</button>
                    <button type="button" class="event-button-primary" wire:click="saveAndSwitchRole" wire:loading.attr="disabled">Save and switch</button>
                </div>
            </section>
        </div>
    @endif
</x-settings-shell>
