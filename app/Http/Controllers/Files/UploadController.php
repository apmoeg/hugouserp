<?php

declare(strict_types=1);

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Store an uploaded file and return its public path/url.
     * Accepts: file, disk?=public, dir?=uploads (auto y/m), visibility?=public|private
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'file' => ['required', 'file', 'max:10240'], // 10MB
            'disk' => ['sometimes', 'string'],
            'dir' => ['sometimes', 'string'],
            'visibility' => ['sometimes', 'in:public,private'],
        ]);

        $disk = (string) $request->input('disk', config('filesystems.default', 'public'));
        $baseDir = trim((string) $request->input('dir', 'uploads'), '/');
        $dir = $baseDir.'/'.now()->format('Y/m');

        $uploaded = $request->file('file');
        $ext = $uploaded->getClientOriginalExtension();
        $name = Str::random(16).($ext ? ('.'.$ext) : '');

        $path = $uploaded->storeAs($dir, $name, ['disk' => $disk, 'visibility' => $request->input('visibility', 'public')]);

        $url = null;
        try {
            $url = Storage::disk($disk)->url($path);
        } catch (\Throwable) {
            // Some disks (local private) may not support url()
        }

        return $this->ok([
            'disk' => $disk,
            'path' => $path,
            'url' => $url,
            'mime' => $uploaded->getClientMimeType(),
            'size' => $uploaded->getSize(),
        ], __('File uploaded successfully'));
    }
}
