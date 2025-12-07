<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ModuleCatalogController extends Controller
{
    public function index()
    {
        $mods = (array) config('modules.available', []);

        return $this->ok($mods);
    }
}
