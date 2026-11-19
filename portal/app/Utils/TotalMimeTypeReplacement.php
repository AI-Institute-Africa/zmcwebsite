<?php

namespace App\Utils;

use App\Support\FileType;
use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypes;

class TotalMimeTypeReplacement implements MimeTypeGuesserInterface
{
    public function isGuesserSupported(): bool
    {
        return true;
    }

    public function guessMimeType(string $path): ?string
    {
        $extension = FileType::normalizeExtension(pathinfo($path, PATHINFO_EXTENSION));

        if (is_file($path) && is_readable($path)) {
            $mime = FileType::mimeFromPath($path, $extension);
            if ($mime) {
                return $mime;
            }
        }

        if ($extension !== '') {
            $mime = FileType::mimeFromExtension($extension);
            if ($mime) {
                return $mime;
            }

            $mimeTypes = MimeTypes::getDefault()->getMimeTypes($extension);
            if ($mimeTypes !== []) {
                return $mimeTypes[0];
            }
        }

        return 'application/octet-stream';
    }
}
