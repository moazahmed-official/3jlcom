<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCaishhaAdRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $currentYear = (int) date('Y');
        
        // Get dynamic limits from settings
        $minDealerWindow = \App\Models\CaishhaSetting::getMinDealerWindowSeconds();
        $maxDealerWindow = \App\Models\CaishhaSetting::getMaxDealerWindowSeconds();
        $minVisibilityPeriod = \App\Models\CaishhaSetting::getMinVisibilityPeriodSeconds();
        $maxVisibilityPeriod = \App\Models\CaishhaSetting::getMaxVisibilityPeriodSeconds();

        return [
            'title' => 'required|string|min:5|max:255',
            'description' => 'required|string|min:10|max:2000',
            'category_id' => 'required|integer|exists:categories,id',
            'brand_id' => 'required|integer|exists:brands,id',
            'model_id' => 'required|integer|exists:models,id',
            'city_id' => 'required|integer|exists:cities,id',
            'country_id' => 'required|integer|exists:countries,id',
            'year' => "required|integer|min:1900|max:{$currentYear}",
            'color' => 'nullable|string|max:100',
            'millage' => 'nullable|numeric|min:0|max:9999999',
            'offers_window_period' => "nullable|integer|min:{$minDealerWindow}|max:{$maxDealerWindow}",
            'sellers_visibility_period' => "nullable|integer|min:{$minVisibilityPeriod}|max:{$maxVisibilityPeriod}",
            'contact_phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'media_ids' => 'nullable|array|max:10',
            'media_ids.*' => 'integer|exists:media,id',
            'period_days' => 'nullable|integer|min:1|max:365',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The ad title is required.',
            'title.min' => 'The ad title must be at least 5 characters.',
            'title.max' => 'The ad title cannot exceed 255 characters.',
            'description.required' => 'The ad description is required.',
            'description.min' => 'The ad description must be at least 10 characters.',
            'description.max' => 'The ad description cannot exceed 2000 characters.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'brand_id.required' => 'Please select a brand.',
            'brand_id.exists' => 'The selected brand is invalid.',
            'model_id.required' => 'Please select a model.',
            'model_id.exists' => 'The selected model is invalid.',
            'city_id.required' => 'Please select a city.',
            'city_id.exists' => 'The selected city is invalid.',
            'country_id.required' => 'Please select a country.',
            'country_id.exists' => 'The selected country is invalid.',
            'year.required' => 'The vehicle year is required.',
            'year.min' => 'The vehicle year must be 1900 or later.',
            'year.max' => 'The vehicle year cannot be in the future.',
            'offers_window_period.min' => 'The offers window must be at least 1 hour (3600 seconds).',
            'offers_window_period.max' => 'The offers window cannot exceed 7 days (604800 seconds).',
            'sellers_visibility_period.max' => 'The visibility period cannot exceed 7 days (604800 seconds).',
            'media_ids.max' => 'You can attach up to 10 media files.',
            'media_ids.*.exists' => 'One or more selected media files are invalid.',
            'period_days.min' => 'The ad period must be at least 1 day.',
            'period_days.max' => 'The ad period cannot exceed 365 days.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'brand_id' => 'brand',
            'model_id' => 'model',
            'city_id' => 'city',
            'country_id' => 'country',
            'offers_window_period' => 'offers window period',
            'sellers_visibility_period' => 'visibility period',
            'media_ids' => 'media files',
            'period_days' => 'ad duration',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422));
    }
}
