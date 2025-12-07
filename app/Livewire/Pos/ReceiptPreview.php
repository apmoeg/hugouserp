<?php

declare(strict_types=1);

namespace App\Livewire\Pos;

use Livewire\Component;

class ReceiptPreview extends Component
{
    public ?int $saleId = null;

    public function render()
    {
        return view('livewire.pos.receipt-preview');
    }
}
