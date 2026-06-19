<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class SiteSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = SiteSetting::instance();

        return response()->json([
            'data' => [
                'name'    => $settings->name,
                'address' => $settings->address,
                'zip_code'=> $settings->zip_code,
                'phone'   => $settings->phone,
                'email'   => $settings->email,
            ],
        ]);
    }
}
