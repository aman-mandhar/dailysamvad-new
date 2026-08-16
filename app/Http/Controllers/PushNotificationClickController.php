<?php

namespace App\Http\Controllers;

use App\Models\PushNotificationDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PushNotificationClickController extends Controller
{
    public function __invoke(string $publicId): RedirectResponse
    {
        $delivery = PushNotificationDelivery::query()->with('notification:id,target_url')->where('public_id', $publicId)->first();
        abort_if($delivery === null, 404);
        $now = now();

        PushNotificationDelivery::query()->whereKey($delivery->getKey())->update([
            'click_count' => DB::raw('click_count + 1'),
            'first_clicked_at' => DB::raw('COALESCE(first_clicked_at, CURRENT_TIMESTAMP)'),
            'last_clicked_at' => $now,
            'updated_at' => $now,
        ]);

        $target = $delivery->notification?->target_url;
        if (! is_string($target) || ! in_array(parse_url($target, PHP_URL_SCHEME), ['http', 'https'], true) || filter_var($target, FILTER_VALIDATE_URL) === false) {
            $target = route('home');
        }

        return redirect()->away($target)->withHeaders([
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
