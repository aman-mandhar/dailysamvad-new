<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Services\Advertisements\AdvertisementTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvertisementImpressionController extends Controller
{
    public function __invoke(Request $request, string $advertisement, AdvertisementTrackingService $tracking): JsonResponse
    {
        $ad = Advertisement::query()->where('uuid', $advertisement)->firstOrFail();
        abort_unless($ad->isRenderable(), 404);
        $tracking->record($ad, 'impressions', $request->string('visitor')->toString());

        return response()->json([], 204);
    }
}
