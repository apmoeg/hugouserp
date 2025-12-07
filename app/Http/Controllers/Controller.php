<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ApiResponse as R;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function ok(mixed $data = null, string $message = 'OK', int $status = 200)
    {
        return R::success($data, $message, $status);
    }

    protected function fail(string $message = 'Error', int $status = 400, array $meta = [])
    {
        return R::error($message, $status, $meta);
    }
}
