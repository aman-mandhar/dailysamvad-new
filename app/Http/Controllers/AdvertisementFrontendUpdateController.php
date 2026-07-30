<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAdvertisementFromFrontendRequest;
use App\Models\Advertisement;
use App\Support\AdvertisementUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdvertisementFrontendUpdateController extends Controller
{
    public function __invoke(UpdateAdvertisementFromFrontendRequest $request, Advertisement $advertisement): RedirectResponse
    {
        DB::transaction(function () use ($request, $advertisement): void {
            $data = $request->validated();
            $data['target_url'] = AdvertisementUrl::normalize($data['target_url'] ?? null);
            $data['updated_by'] = $request->user()->getKey();
            if ($data['status'] === 'active' && $request->user()->cannot('publish', $advertisement)) {
                abort(403);
            }
            if ($data['status'] === 'paused' && $request->user()->cannot('pause', $advertisement)) {
                abort(403);
            }
            $advertisement->update($data);
        });

        return back()->with('success', 'Advertisement updated.');
    }
}
