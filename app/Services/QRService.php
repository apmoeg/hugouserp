<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\QRServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Storage;

class QRService implements QRServiceInterface
{
    use HandlesServiceErrors;

    public function make(string $payload, string $filename): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($payload, $filename) {
                $safe = preg_replace('/[^A-Za-z0-9_.-]+/', '-', $filename);
                $dir = 'qrcodes';
                if (class_exists('SimpleSoftwareIO\\QrCode\\Facade')) {
                    $path = $dir.'/'.$safe.'.png';
                    $png = \SimpleSoftwareIO\QrCode\Facade::format('png')->size(300)->generate($payload);
                    Storage::disk('public')->put($path, $png);

                    return ['path' => $path, 'mime' => 'image/png'];
                }

                $path = $dir.'/'.$safe.'.txt';
                Storage::disk('public')->put($path, $payload);

                return ['path' => $path, 'mime' => 'text/plain'];
            },
            operation: 'make',
            context: ['filename' => $filename]
        );
    }
}
