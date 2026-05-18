<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group_name',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [];
    }

    public static function getByKey(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'json' => json_decode($setting->value ?? 'null', true) ?? $default,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOL),
            'number' => is_numeric($setting->value) ? (float) $setting->value : $default,
            default => $setting->value ?? $default,
        };
    }

    public static function updateByKey(string $key, mixed $value, string $type = 'text', ?string $groupName = null): self
    {
        $storedValue = match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => $value,
        };

        return static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'group_name' => $groupName,
            ]
        );
    }
}
