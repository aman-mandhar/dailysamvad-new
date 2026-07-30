<?php

namespace App\Http\Requests;

use App\Enums\AdvertisementStatus;
use App\Support\AdvertisementUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdvertisementFromFrontendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateFromFrontend', $this->route('advertisement')) ?? false;
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'target_url' => ['nullable', 'string', 'max:2048', function ($attribute, $value, $fail) {
            if (AdvertisementUrl::normalize($value) === null && filled($value)) {
                $fail('Use a safe HTTP, HTTPS, or internal relative URL.');
            }
        }], 'status' => ['required', Rule::in([AdvertisementStatus::Active->value, AdvertisementStatus::Paused->value, AdvertisementStatus::Scheduled->value])], 'start_at' => ['nullable', 'date'], 'end_at' => ['nullable', 'date', 'after:start_at'], 'open_in_new_tab' => ['boolean'], 'sponsored' => ['boolean'], 'nofollow' => ['boolean']];
    }
}
