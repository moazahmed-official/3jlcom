<?php

namespace App\Http\Requests\AdUpgradeRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdUpgradeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // User must own the ad or be admin
        $ad = $this->route('ad');
        return $ad && ($ad->user_id === auth()->id() || auth()->user()->role === 'admin');
    }

    public function rules(): array
    {
        return [
            'requested_unique_type_id' => ['required', 'integer', 'exists:unique_ad_type_definitions,id'],
            'user_message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'requested_unique_type_id.required' => 'Please select a unique ad type',
            'requested_unique_type_id.exists' => 'The selected unique ad type does not exist',
            'user_message.max' => 'Your message cannot exceed 1000 characters',
        ];
    }
}
