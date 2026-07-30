<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Services\Advertisements\AdvertisementTrackingService;
use App\Support\AdvertisementUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdvertisementClickController extends Controller
{
    public function __invoke(Request $request, string $advertisement, AdvertisementTrackingService $tracking): RedirectResponse
    {
        $ad = Advertisement::query()->where('uuid', $advertisement)->firstOrFail();
        abort_unless($ad->isRenderable(), 404);
        $destination = AdvertisementUrl::normalize($ad->target_url);
        if ($destination === null) {
            abort(404);
        }
        $tracking->record($ad, 'clicks', $request->cookie('ad_visitor') ?: $request->session()->getId());

        return redirect()->away(url($destination));
    }
}
