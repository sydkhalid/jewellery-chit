<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SafeUpload implements ValidationRule
{
    /**
     * @param  array<int, string>  $allowedExtensions
     */
    public function __construct(
        private readonly array $allowedExtensions
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The :attribute must be a valid uploaded file.');

            return;
        }

        $clientExtension = strtolower((string) $value->getClientOriginalExtension());
        $guessedExtension = strtolower((string) $value->extension());
        $allowedExtensions = array_map('strtolower', $this->allowedExtensions);

        if ($clientExtension === '' || ! in_array($clientExtension, $allowedExtensions, true)) {
            $fail('The :attribute file type is not allowed.');

            return;
        }

        if ($guessedExtension !== '' && ! in_array($guessedExtension, $allowedExtensions, true)) {
            $fail('The :attribute file content does not match the allowed file types.');

            return;
        }

        if ($this->isExecutableExtension($clientExtension)) {
            $fail('Executable uploads are not allowed.');
        }
    }

    private function isExecutableExtension(string $extension): bool
    {
        return in_array($extension, [
            'bat',
            'cmd',
            'com',
            'exe',
            'html',
            'jar',
            'js',
            'msi',
            'phar',
            'php',
            'phtml',
            'ps1',
            'sh',
            'svg',
        ], true);
    }
}
