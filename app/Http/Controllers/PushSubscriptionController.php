<?php

namespace App\Http\Controllers;

use App\Http\Requests\Push\DeletePushSubscriptionRequest;
use App\Http\Requests\Push\StorePushSubscriptionRequest;
use App\Services\Push\PushSubscriptionService;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request, PushSubscriptionService $subscriptions): JsonResponse
    {
        $data = $request->safe()->except('token');
        $data['user_agent'] = str($request->userAgent())->limit(1024, '')->toString() ?: null;
        $result = $subscriptions->register($request->validated('token'), $request->user(), $data);

        return response()->json([
            'success' => true,
            'status' => $result['created'] ? 'subscribed' : 'updated',
        ], $result['created'] ? 201 : 200);
    }

    public function destroy(DeletePushSubscriptionRequest $request, PushSubscriptionService $subscriptions): JsonResponse
    {
        $subscriptions->unsubscribe($request->validated('token'));

        return response()->json([
            'success' => true,
            'status' => 'unsubscribed',
        ]);
    }
}
