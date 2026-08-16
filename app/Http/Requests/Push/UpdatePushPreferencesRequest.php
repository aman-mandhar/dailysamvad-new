<?php

namespace App\Http\Requests\Push;

class UpdatePushPreferencesRequest extends PushPreferenceRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'topic_ids' => ['present', 'array', 'max:500'],
            'topic_ids.*' => ['integer'],
        ];
    }
}
