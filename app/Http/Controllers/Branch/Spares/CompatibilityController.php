<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\Spares;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompatibilityAttachRequest;
use App\Http\Requests\CompatibilityDetachRequest;
use App\Services\Contracts\SparesServiceInterface as Spares;
use Illuminate\Http\Request;

class CompatibilityController extends Controller
{
    public function __construct(protected Spares $spares) {}

    public function index(Request $request)
    {
        $pid = (int) $request->integer('product_id');

        return $this->ok($this->spares->listCompatibility($pid));
    }

    public function attach(CompatibilityAttachRequest $request)
    {
        $data = $request->validated();
        $this->spares->attach($data['product_id'], $data['compatible_with_id']);

        return $this->ok(null, __('Attached'));
    }

    public function detach(CompatibilityDetachRequest $request)
    {
        $data = $request->validated();
        $this->spares->detach($data['product_id'], $data['compatible_with_id']);

        return $this->ok(null, __('Detached'));
    }
}
