<?php

namespace App\Rules;

use App\Support\FileType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class FileSignature implements ValidationRule
{
    /**
     * @param array<int, string> $allowedExtensions
     */
    public function __construct(private array $allowedExtensions)
    {
        $this->allowedExtensions = array_map(
            fn (string $extension): string => FileType::normalizeExtension($extension),
            $allowedExtensions
        );
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        if (!FileType::uploadedFileLooksValid($value, $this->allowedExtensions)) {
            $fail('The :attribute must be a valid file of type: ' . implode(', ', $this->allowedExtensions) . '.');
        }
    }
}
