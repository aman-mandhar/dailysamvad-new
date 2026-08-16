<?php

namespace App\Http\Requests\Push;

use Illuminate\Foundation\Http\FormRequest;

class PushPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'min:20', 'max:4096'],
            'device_uuid' => ['required', 'uuid'],
        ];
    }
}
