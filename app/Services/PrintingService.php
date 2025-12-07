<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\PrintingServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class PrintingService implements PrintingServiceInterface
{
    use HandlesServiceErrors;

    public function renderHtml(string $view, array $data = []): string
    {
        return $this->handleServiceOperation(
            callback: function () use ($view, $data) {
                if (! View::exists($view)) {
                    return '<pre>'.e(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)).'</pre>';
                }

                return (string) view($view, $data)->render();
            },
            operation: 'renderHtml',
            context: ['view' => $view],
            defaultValue: ''
        );
    }

    public function renderPdfOrHtml(string $view, array $data, string $filename): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($view, $data, $filename) {
                $safe = preg_replace('/[^A-Za-z0-9_.-]+/', '-', $filename);
                $path = 'prints/'.$safe.(class_exists('Barryvdh\\DomPDF\\Facade\\Pdf') ? '.pdf' : '.html');

                if (class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($this->renderHtml($view, $data));
                    Storage::disk('local')->put($path, $pdf->output());

                    return ['path' => $path, 'mime' => 'application/pdf'];
                }

                Storage::disk('local')->put($path, $this->renderHtml($view, $data));

                return ['path' => $path, 'mime' => 'text/html'];
            },
            operation: 'renderPdfOrHtml',
            context: ['view' => $view, 'filename' => $filename]
        );
    }
}
