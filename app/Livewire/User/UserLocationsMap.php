<?php

namespace App\Livewire\User;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Member Locations Map')]
class UserLocationsMap extends Component
{
    public function render()
    {
        return view('livewire.user.user-locations-map');
    }
}
