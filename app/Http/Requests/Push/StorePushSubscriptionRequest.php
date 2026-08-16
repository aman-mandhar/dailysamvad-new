<?php

namespace App\Http\Requests\Push;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StorePushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'min:20', 'max:4096'],
            'device_uuid' => ['nullable', 'uuid'],
            'browser' => ['nullable', 'string', 'max:64'],
            'browser_version' => ['nullable', 'string', 'max:32'],
            'platform' => ['nullable', 'string', 'max:64'],
            'device_type' => ['nullable', Rule::in(['desktop', 'mobile', 'tablet', 'unknown'])],
            'language' => ['nullable', 'string', 'max:16'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'permission_status' => ['required', Rule::in(['granted'])],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'The push subscription data is invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
