<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\BarcodeServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Storage;

class BarcodeService implements BarcodeServiceInterface
{
    use HandlesServiceErrors;

    protected string $disk;

    protected string $dir;

    public function __construct()
    {
        $this->disk = (string) config('filesystems.default', 'public');
        $this->dir = (string) config('erp.barcodes.dir', 'barcodes');
    }

    public function ean13(string $seed): string
    {
        return $this->handleServiceOperation(
            callback: function () use ($seed) {
                $num = preg_replace('/\D+/', '', $seed);
                $num = str_pad(substr($num, 0, 12), 12, '0');
                $checksum = $this->ean13Checksum($num);

                return $num.$checksum;
            },
            operation: 'ean13',
            context: ['seed' => $seed]
        );
    }

    protected function ean13Checksum(string $twelve): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $n = (int) $twelve[$i];
            $sum += ($i % 2 === 0) ? $n : $n * 3;
        }

        return (10 - ($sum % 10)) % 10;
    }

    public function storeEan13(string $seed): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($seed) {
                $code = $this->ean13($seed);
                $file = $this->dir.'/EAN13_'.$code.'.txt';

                Storage::disk($this->disk)->put($file, $code);

                return ['path' => $file, 'code' => $code];
            },
            operation: 'storeEan13',
            context: ['seed' => $seed]
        );
    }
}
