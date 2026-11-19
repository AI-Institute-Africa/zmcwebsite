<?php

namespace App\Http\Controllers;

use App\Rules\FileSignature;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\UploadedFile;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function storePublicFile(UploadedFile $file, string $directory, string $filename): string
    {
        $path = $file->storeAs($directory, $filename, 'public');

        // Never persist a document row when the uploaded file was not actually
        // written. This prevents applications from advancing with metadata
        // pointing to a missing file, which later appears as a false document
        // or security failure during intake review.
        if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            throw new \RuntimeException('The uploaded file could not be saved. Please try uploading it again.');
        }

        return $path;
    }

    protected function uploadFileRules(array $extensions, int $maxKb, string $presence = 'nullable'): array
    {
        return [
            $presence,
            'file',
            'extensions:' . implode(',', $extensions),
            new FileSignature($extensions),
            "max:{$maxKb}",
        ];
    }

    protected function imageUploadRules(int $maxKb, array $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'], string $presence = 'nullable'): array
    {
        return $this->uploadFileRules($extensions, $maxKb, $presence);
    }
}
