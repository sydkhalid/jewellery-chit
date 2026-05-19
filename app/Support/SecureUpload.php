<?php

namespace App\Support;

use App\Rules\SafeUpload;
use Illuminate\Validation\Rules\File;

class SecureUpload
{
    /**
     * @return array<int, mixed>
     */
    public static function image(int $maxKilobytes = 2048): array
    {
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];

        return [
            'file',
            File::image()->extensions($extensions)->max($maxKilobytes),
            new SafeUpload($extensions),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function document(int $maxKilobytes = 5120): array
    {
        $extensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

        return [
            'file',
            File::types($extensions)->extensions($extensions)->max($maxKilobytes),
            new SafeUpload($extensions),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function agreement(int $maxKilobytes = 5120): array
    {
        $extensions = ['pdf', 'jpg', 'jpeg', 'png'];

        return [
            'file',
            File::types($extensions)->extensions($extensions)->max($maxKilobytes),
            new SafeUpload($extensions),
        ];
    }
}
