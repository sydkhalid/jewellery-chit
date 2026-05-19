<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    protected static function booted(): void
    {
        static::saved(fn (ShopSetting $setting): bool => static::forgetCache($setting));
        static::deleted(fn (ShopSetting $setting): bool => static::forgetCache($setting));
    }

    public static function getByKey(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember(
            static::cacheKey($key),
            now()->addSeconds((int) config('jewellery.cache.settings_ttl', 3600)),
            fn (): ?self => static::query()->where('key', $key)->first()
        );

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

        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'group_name' => $groupName,
            ]
        );

        static::forgetCache($setting);

        return $setting;
    }

    private static function cacheKey(string $key): string
    {
        return 'settings:key:'.$key;
    }

    private static function forgetCache(self $setting): bool
    {
        Cache::forget(static::cacheKey($setting->key));
        Cache::forget('settings:all');

        if ($setting->group_name) {
            Cache::forget('settings:group:'.$setting->group_name);
        }

        return true;
    }
}
