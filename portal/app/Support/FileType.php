<?php

namespace App\Support;

use Illuminate\Support\Str;

class FileType
{
    private const MIME_BY_EXTENSION = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'zip' => 'application/zip',
        'txt' => 'text/plain',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'webm' => 'video/webm',
    ];

    public static function safeUploadedMime($file): ?string
    {
        try {
            $path = method_exists($file, 'getRealPath') ? $file->getRealPath() : null;
            if ($path) {
                $mime = self::mimeFromPath($path, self::uploadedExtension($file));
                if ($mime) {
                    return $mime;
                }
            }
        } catch (\Throwable) {
            // Fall back to extension-only mapping below.
        }

        $extension = self::uploadedExtension($file);

        return self::mimeFromExtension($extension);
    }

    public static function uploadedExtension($file): string
    {
        try {
            if (method_exists($file, 'getClientOriginalExtension')) {
                return self::normalizeExtension((string) $file->getClientOriginalExtension());
            }
        } catch (\Throwable) {
            return '';
        }

        return '';
    }

    public static function safeHashName($file): string
    {
        $extension = self::uploadedExtension($file);

        return Str::random(40) . ($extension ? ".{$extension}" : '');
    }

    public static function mimeFromExtension(string $extension): ?string
    {
        return self::MIME_BY_EXTENSION[self::normalizeExtension($extension)] ?? null;
    }

    public static function mimeFromPath(string $path, ?string $extension = null): ?string
    {
        $header = self::readHeader($path, 512);

        return self::mimeFromHeader($header, $extension);
    }

    public static function mimeFromHeader(string $header, ?string $extension = null): ?string
    {
        if ($header === '') {
            return $extension ? self::mimeFromExtension($extension) : null;
        }

        if (str_starts_with($header, '%PDF')) {
            return 'application/pdf';
        }

        if (str_starts_with($header, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($header, "\x89PNG\r\n\x1A\n")) {
            return 'image/png';
        }

        if (str_starts_with($header, "GIF87a") || str_starts_with($header, "GIF89a")) {
            return 'image/gif';
        }

        if (str_starts_with($header, "RIFF") && substr($header, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        if (str_starts_with($header, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) {
            return match (self::normalizeExtension((string) $extension)) {
                'xls' => 'application/vnd.ms-excel',
                'ppt' => 'application/vnd.ms-powerpoint',
                default => 'application/msword',
            };
        }

        if (
            str_starts_with($header, "PK\x03\x04") ||
            str_starts_with($header, "PK\x05\x06") ||
            str_starts_with($header, "PK\x07\x08")
        ) {
            return match (self::normalizeExtension((string) $extension)) {
                'zip' => 'application/zip',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                default => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            };
        }

        if (str_starts_with($header, 'ID3') || str_starts_with($header, "\xFF\xFB") || str_starts_with($header, "\xFF\xF3") || str_starts_with($header, "\xFF\xF2")) {
            return 'audio/mpeg';
        }

        if (str_starts_with($header, "RIFF") && substr($header, 8, 4) === 'WAVE') {
            return 'audio/wav';
        }

        if (str_starts_with($header, "RIFF") && substr($header, 8, 4) === 'AVI ') {
            return 'video/x-msvideo';
        }

        if (substr($header, 4, 4) === 'ftyp') {
            $majorBrand = substr($header, 8, 4);

            return in_array($majorBrand, ['qt  ', 'moov'], true) ? 'video/quicktime' : 'video/mp4';
        }

        if (str_starts_with($header, "\x1A\x45\xDF\xA3")) {
            return 'video/webm';
        }

        if (self::looksLikeText($header)) {
            return 'text/plain';
        }

        return $extension ? self::mimeFromExtension($extension) : null;
    }

    public static function looksLikeExtension(string $path, string $extension): bool
    {
        $extension = self::normalizeExtension($extension);
        $header = self::readHeader($path, 512);

        if ($header === '') {
            return false;
        }

        return match ($extension) {
            'pdf' => str_starts_with($header, '%PDF'),
            'jpg', 'jpeg' => str_starts_with($header, "\xFF\xD8\xFF"),
            'png' => str_starts_with($header, "\x89PNG\r\n\x1A\n"),
            'gif' => str_starts_with($header, "GIF87a") || str_starts_with($header, "GIF89a"),
            'webp' => str_starts_with($header, "RIFF") && substr($header, 8, 4) === 'WEBP',
            'doc', 'xls', 'ppt' => str_starts_with($header, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"),
            'docx', 'xlsx', 'pptx', 'zip' => str_starts_with($header, "PK\x03\x04") || str_starts_with($header, "PK\x05\x06") || str_starts_with($header, "PK\x07\x08"),
            'txt' => self::looksLikeText($header),
            'mp3' => str_starts_with($header, 'ID3') || str_starts_with($header, "\xFF\xFB") || str_starts_with($header, "\xFF\xF3") || str_starts_with($header, "\xFF\xF2"),
            'wav' => str_starts_with($header, "RIFF") && substr($header, 8, 4) === 'WAVE',
            'mp4', 'mov' => substr($header, 4, 4) === 'ftyp',
            'avi' => str_starts_with($header, "RIFF") && substr($header, 8, 4) === 'AVI ',
            'webm' => str_starts_with($header, "\x1A\x45\xDF\xA3"),
            default => true,
        };
    }

    public static function uploadedFileLooksValid($file, array $allowedExtensions): bool
    {
        $extension = self::uploadedExtension($file);

        if (!in_array($extension, array_map('strtolower', $allowedExtensions), true)) {
            return false;
        }

        try {
            $path = method_exists($file, 'getRealPath') ? $file->getRealPath() : null;
        } catch (\Throwable) {
            $path = null;
        }

        if (!$path) {
            return false;
        }

        return self::looksLikeExtension($path, $extension);
    }

    public static function normalizeExtension(string $extension): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extension) ?? '');
    }

    private static function readHeader(string $path, int $bytes): string
    {
        $handle = @fopen($path, 'rb');
        if (!$handle) {
            return '';
        }

        $header = (string) fread($handle, $bytes);
        fclose($handle);

        return $header;
    }

    private static function looksLikeText(string $header): bool
    {
        if ($header === '') {
            return false;
        }

        return !str_contains($header, "\0") && mb_check_encoding($header, 'UTF-8');
    }
}
