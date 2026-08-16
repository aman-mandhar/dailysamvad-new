<?php

namespace App\Http\Controllers;

use App\Http\Requests\Push\PushPreferenceRequest;
use App\Http\Requests\Push\UpdatePushPreferencesRequest;
use App\Services\Push\PushPreferenceService;
use Illuminate\Http\JsonResponse;

class PushPreferenceController extends Controller
{
    public function show(PushPreferenceRequest $request, PushPreferenceService $preferences): JsonResponse
    {
        $subscription = $preferences->resolve($request->validated('token'), $request->validated('device_uuid'));

        return $this->response($preferences->state($subscription));
    }

    public function update(UpdatePushPreferencesRequest $request, PushPreferenceService $preferences): JsonResponse
    {
        $subscription = $preferences->resolve($request->validated('token'), $request->validated('device_uuid'));

        return $this->response($preferences->sync($subscription, $request->validated('topic_ids')));
    }

    private function response(array $state): JsonResponse
    {
        return response()->json(['success' => true, ...$state])
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }
}
