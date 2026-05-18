<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShopSettingResource;
use App\Models\ShopSetting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settings
    ) {
    }

    public function index(): JsonResponse
    {
        $settings = $this->settings->getAllSettings();

        return response()->json([
            'success' => true,
            'message' => 'Settings fetched successfully',
            'data' => [
                'settings' => ShopSettingResource::collection($settings),
                'groups' => $settings
                    ->groupBy('group_name')
                    ->map(fn ($items) => ShopSettingResource::collection($items)->resolve(request())),
            ],
        ]);
    }

    public function show(string $key): JsonResponse
    {
        $setting = ShopSetting::query()->where('key', $key)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Setting fetched successfully',
            'data' => [
                'setting' => new ShopSettingResource($setting),
            ],
        ]);
    }
}
