<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingUpdateRequest;
use App\Http\Resources\ShopSettingResource;
use App\Models\ShopSetting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settings
    ) {
    }

    public function index(): View
    {
        return $this->settingsView('shop');
    }

    public function update(SettingUpdateRequest $request): JsonResponse|RedirectResponse
    {
        $settings = $this->settings->updateShopSettingsBundle($request->validated());
        $activeTab = (string) $request->input('active_tab', 'shop');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => [
                    'settings' => ShopSettingResource::collection($settings),
                    'redirect' => route('settings.'.$this->normalizeTab($activeTab)),
                ],
            ]);
        }

        return redirect()->route('settings.'.$this->normalizeTab($activeTab))->with('success', 'Settings updated successfully');
    }

    public function shop(): View
    {
        return $this->settingsView('shop');
    }

    public function receipt(): View
    {
        return $this->settingsView('receipt');
    }

    public function chit(): View
    {
        return $this->settingsView('chit');
    }

    public function message(): View
    {
        return $this->settingsView('message');
    }

    public function backup(): View
    {
        return $this->settingsView('backup');
    }

    private function settingsView(string $activeTab): View
    {
        $settings = $this->settings->getAllSettings();

        return view('settings.index', [
            'settings' => $settings,
            'values' => $this->values($settings),
            'activeTab' => $this->normalizeTab($activeTab),
        ]);
    }

    /**
     * @param  iterable<int, ShopSetting>  $settings
     * @return array<string, mixed>
     */
    private function values(iterable $settings): array
    {
        $values = [];

        foreach ($settings as $setting) {
            $values[$setting->key] = match ($setting->type) {
                'json' => json_decode($setting->value ?? 'null', true),
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOL),
                'number' => is_numeric($setting->value) ? (float) $setting->value : null,
                default => $setting->value,
            };
        }

        return $values;
    }

    private function normalizeTab(string $tab): string
    {
        return in_array($tab, ['shop', 'receipt', 'chit', 'message', 'backup'], true) ? $tab : 'shop';
    }
}
