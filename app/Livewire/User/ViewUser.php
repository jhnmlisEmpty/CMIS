<?php

namespace App\Livewire\User;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('View Member')]
class ViewUser extends Component
{
    public User $user;

    public function mount(?User $user = null): void
    {
        $user ??= auth()->user();

        if (auth()->id() !== $user->id) {
            Gate::authorize('users.view');
            abort_unless(auth()->user()->canAccessMember($user), 403);
        }

        $this->user = $user;
    }

    public function render()
    {
        return view('livewire.user.view-user');
    }
}
