<?php

declare(strict_types=1);

namespace App\Livewire\Pos;

use Livewire\Component;

class HoldList extends Component
{
    public array $holds = [];

    public function render()
    {
        return view('livewire.pos.hold-list');
    }
}
