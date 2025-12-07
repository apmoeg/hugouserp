<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use Livewire\Component;

class LoadingSpinner extends Component
{
    public function render()
    {
        return view('livewire.shared.loading-spinner');
    }
}
