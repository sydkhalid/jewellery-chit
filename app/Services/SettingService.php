<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\ShopSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    /**
     * @return Collection<int, ShopSetting>
     */
    public function getAllSettings(): Collection
    {
        return Cache::remember(
            'settings:all',
            now()->addSeconds((int) config('jewellery.cache.settings_ttl', 3600)),
            fn (): Collection => ShopSetting::query()->orderBy('group_name')->orderBy('key')->get()
        );
    }

    /**
     * @return Collection<int, ShopSetting>
     */
    public function getSettingsByGroup(string $group): Collection
    {
        return Cache::remember(
            'settings:group:'.$group,
            now()->addSeconds((int) config('jewellery.cache.settings_ttl', 3600)),
            fn (): Collection => ShopSetting::query()->where('group_name', $group)->orderBy('key')->get()
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return ShopSetting::getByKey($key, $default);
    }

    public function set(string $key, mixed $value, string $type = 'text', ?string $group = null): ShopSetting
    {
        $oldSetting = ShopSetting::query()->where('key', $key)->first();
        $oldValues = $oldSetting?->toArray();
        $storedValue = $this->storedValue($value, $type);

        $setting = ShopSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'group_name' => $group,
                $oldSetting ? 'updated_by' : 'created_by' => Auth::id(),
            ]
        );

        $this->logSettingUpdate($setting, $oldValues, $setting->refresh()->toArray());

        return $setting;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateShopSettings(array $data): void
    {
        $this->set('shop_name', $data['shop_name'], 'text', 'shop');

        if (($data['shop_logo'] ?? null) instanceof UploadedFile) {
            $this->set('shop_logo', $this->uploadLogo($data['shop_logo']), 'file', 'shop');
        }

        $this->set('shop_address', $data['shop_address'] ?? null, 'text', 'shop');
        $this->set('shop_mobile', $data['shop_mobile'] ?? null, 'text', 'shop');
        $this->set('shop_email', $data['shop_email'] ?? null, 'text', 'shop');
        $this->set('gstin', $data['gstin'] ?? null, 'text', 'shop');
        $this->set('financial_year', $data['financial_year'], 'text', 'shop');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateReceiptSettings(array $data): void
    {
        $this->set('receipt_prefix', $data['receipt_prefix'], 'text', 'receipt');
        $this->set('payment_number_prefix', $data['payment_number_prefix'], 'text', 'receipt');
        $this->set('closure_number_prefix', $data['closure_number_prefix'], 'text', 'receipt');
        $this->set('refund_number_prefix', $data['refund_number_prefix'], 'text', 'receipt');
        $this->set('invoice_number_prefix', $data['invoice_number_prefix'], 'text', 'receipt');
        $this->set('handover_number_prefix', $data['handover_number_prefix'], 'text', 'receipt');
        $this->set('terms_and_conditions', $data['terms_and_conditions'] ?? null, 'text', 'receipt');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateChitSettings(array $data): void
    {
        $this->set('chit_number_prefix', $data['chit_number_prefix'], 'text', 'chit');
        $this->set('default_grace_period_days', $data['default_grace_period_days'] ?? 0, 'number', 'chit');
        $this->set('default_late_fee_type', $data['default_late_fee_type'] ?? 'none', 'text', 'chit');
        $this->set('default_late_fee_value', $data['default_late_fee_value'] ?? 0, 'number', 'chit');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateMessageSettings(array $data): void
    {
        $this->set('whatsapp_enabled', (bool) ($data['whatsapp_enabled'] ?? false), 'boolean', 'message');
        $this->set('whatsapp_api_url', $data['whatsapp_api_url'] ?? null, 'text', 'message');
        $this->set('whatsapp_api_key', $data['whatsapp_api_key'] ?? null, 'text', 'message');
        $this->set('sms_enabled', (bool) ($data['sms_enabled'] ?? false), 'boolean', 'message');
        $this->set('sms_api_url', $data['sms_api_url'] ?? null, 'text', 'message');
        $this->set('sms_api_key', $data['sms_api_key'] ?? null, 'text', 'message');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBackupSettings(array $data): void
    {
        $this->set('backup_enabled', (bool) ($data['backup_enabled'] ?? false), 'boolean', 'backup');
        $this->set('backup_disk', $data['backup_disk'] ?? 'local', 'text', 'backup');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, ShopSetting>
     */
    public function updateShopSettingsBundle(array $data): Collection
    {
        return DB::transaction(function () use ($data): Collection {
            $this->updateShopSettings($data);
            $this->updateReceiptSettings($data);
            $this->updateChitSettings($data);
            $this->updateMessageSettings($data);

            if (Auth::user()?->can('settings.backup')) {
                $this->updateBackupSettings($data);
            }

            return $this->getAllSettings();
        });
    }

    public function uploadLogo(UploadedFile $file): string
    {
        $currentLogo = ShopSetting::getByKey('shop_logo');
        $path = $file->store('shop-settings', 'public');

        if (is_string($currentLogo) && $currentLogo !== '' && Storage::disk('public')->exists($currentLogo)) {
            Storage::disk('public')->delete($currentLogo);
        }

        return $path;
    }

    private function storedValue(mixed $value, string $type): ?string
    {
        return match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => $value === null ? null : (string) $value,
        };
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logSettingUpdate(ShopSetting $setting, ?array $oldValues, ?array $newValues): void
    {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => 'updated',
            'module' => 'shop_settings',
            'description' => "Setting {$setting->key} updated.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => ShopSetting::class,
            'auditable_id' => $setting->id,
            'event' => 'update',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
