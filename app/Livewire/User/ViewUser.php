<?php

namespace App\Livewire\User;

use App\Models\User;
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

        abort_if(auth()->user()?->hasRole(User::ROLE_MEMBER) && auth()->id() !== $user->id, 403);

        $this->user = $user;
    }

    public function render()
    {
        return view('livewire.user.view-user');
    }
}
