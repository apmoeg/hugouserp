<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @deprecated Use App\Livewire\Inventory\Services\Form instead
 * This class is kept for backward compatibility but redirects to the form page
 */
class ServiceProductForm extends Component
{
    public ?int $productId = null;

    public ?int $moduleId = null;

    #[On('openServiceForm')]
    public function redirectToCreate(?int $moduleId = null): mixed
    {
        $params = [];
        if ($moduleId) {
            $params['moduleId'] = $moduleId;
        }

        $this->redirectRoute('app.inventory.services.create', $params, navigate: true);
    }

    #[On('editService')]
    public function redirectToEdit(int $productId): void
    {
        $this->redirectRoute('app.inventory.services.edit', ['service' => $productId], navigate: true);
    }

    public function render()
    {
        return view('livewire.inventory.service-product-form');
    }
}
