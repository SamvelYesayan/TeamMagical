<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private function payload(): array
    {
        return [
            'company_name' => (string) Setting::get('company_name'),
            'reminder_hours' => (int) Setting::get('reminder_hours'),
        ];
    }

    public function show(): JsonResponse
    {
        return response()->json($this->payload());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'min:2', 'max:80'],
            'reminder_hours' => ['required', 'integer', 'min:1', 'max:168'],
        ]);

        Setting::put('company_name', $data['company_name']);
        Setting::put('reminder_hours', $data['reminder_hours']);
        ActivityLogger::log($request->user(), 'settings.updated', null, 'Թարմացվեցին կարգավորումները', $data);

        return response()->json($this->payload());
    }
}
