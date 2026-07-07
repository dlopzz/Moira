<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SiteSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = SiteSetting::instance();

        return response()->json([
            'data' => [
                'name' => $settings->name,
                'logo' => $settings->logo ? Storage::url($settings->logo) : null,
                'promo_text' => $settings->promo_text ?? null,
                'address' => $settings->address,
                'zip_code' => $settings->zip_code,
                'phone' => $settings->phone,
                'email' => $settings->email,
                'recaptcha_enabled' => (bool) $settings->recaptcha_enabled,
                'cookie_notice_enabled' => (bool) $settings->cookie_notice_enabled,
                'cookie_notice_text' => $settings->cookie_notice_text ?? '',
            ],
        ]);
    }
}
