<?php

namespace App\Http\Requests;

use App\Models\Ad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCaishhaAdRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Support both route model binding and raw id parameter named 'ad'
        $routeAd = $this->route('ad');

        if ($routeAd instanceof Ad) {
            $ad = $routeAd;
        } else {
            $ad = Ad::find($routeAd);
        }

        if (!$ad) {
            return false;
        }

        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Owner or admin can update
        return $user->id === $ad->user_id || (method_exists($user, 'isAdmin') && $user->isAdmin());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $currentYear = (int) date('Y');
        $ad = Ad::with('caishhaAd.offers')->find($this->route('id'));
        $hasOffers = $ad && $ad->caishhaAd && $ad->caishhaAd->offers_count > 0;

        $rules = [
            'title' => 'sometimes|string|min:5|max:255',
            'description' => 'sometimes|string|min:10|max:2000',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'brand_id' => 'sometimes|integer|exists:brands,id',
            'model_id' => 'sometimes|integer|exists:models,id',
            'city_id' => 'sometimes|integer|exists:cities,id',
            'country_id' => 'sometimes|integer|exists:countries,id',
            'year' => "sometimes|integer|min:1900|max:{$currentYear}",
            'contact_phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'media_ids' => 'nullable|array|max:10',
            'media_ids.*' => 'integer|exists:media,id',
            'period_days' => 'nullable|integer|min:1|max:365',
        ];

        // Window periods cannot be changed if there are existing offers
        if (!$hasOffers) {
            // Get dynamic limits from settings
            $minDealerWindow = \App\Models\CaishhaSetting::getMinDealerWindowSeconds();
            $maxDealerWindow = \App\Models\CaishhaSetting::getMaxDealerWindowSeconds();
            $minVisibilityPeriod = \App\Models\CaishhaSetting::getMinVisibilityPeriodSeconds();
            $maxVisibilityPeriod = \App\Models\CaishhaSetting::getMaxVisibilityPeriodSeconds();
            
            $rules['offers_window_period'] = "sometimes|integer|min:{$minDealerWindow}|max:{$maxDealerWindow}";
            $rules['sellers_visibility_period'] = "sometimes|integer|min:{$minVisibilityPeriod}|max:{$maxVisibilityPeriod}";
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $minDealerWindowHours = \App\Models\CaishhaSetting::getMinDealerWindowSeconds() / 3600;
        $maxDealerWindowDays = \App\Models\CaishhaSetting::getMaxDealerWindowSeconds() / 86400;
        $maxVisibilityDays = \App\Models\CaishhaSetting::getMaxVisibilityPeriodSeconds() / 86400;

        return [
            'title.min' => 'The ad title must be at least 5 characters.',
            'title.max' => 'The ad title cannot exceed 255 characters.',
            'description.min' => 'The ad description must be at least 10 characters.',
            'description.max' => 'The ad description cannot exceed 2000 characters.',
            'category_id.exists' => 'The selected category is invalid.',
            'brand_id.exists' => 'The selected brand is invalid.',
            'model_id.exists' => 'The selected model is invalid.',
            'city_id.exists' => 'The selected city is invalid.',
            'country_id.exists' => 'The selected country is invalid.',
            'year.min' => 'The vehicle year must be 1900 or later.',
            'year.max' => 'The vehicle year cannot be in the future.',
            'offers_window_period.min' => "The offers window must be at least {$minDealerWindowHours} hours.",
            'offers_window_period.max' => "The offers window cannot exceed {$maxDealerWindowDays} days.",
            'sellers_visibility_period.max' => "The visibility period cannot exceed {$maxVisibilityDays} days.",
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
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ad = Ad::with('caishhaAd.offers')->find($this->route('id'));
            
            if ($ad && $ad->caishhaAd && $ad->caishhaAd->offers_count > 0) {
                // Check if user is trying to change window periods when offers exist
                if ($this->has('offers_window_period') || $this->has('sellers_visibility_period')) {
                    $validator->errors()->add(
                        'offers_window_period',
                        'Window periods cannot be changed once offers have been submitted.'
                    );
                }
            }
        });
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

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 403,
            'message' => 'Unauthorized',
            'errors' => ['authorization' => ['You do not have permission to update this ad']]
        ], 403));
    }
}
