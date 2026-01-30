<?php

namespace App\Http\Requests;

use App\Models\Ad;
use App\Models\CaishhaOffer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCaishhaOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        // Support both route model binding and raw id parameter
        $routeOffer = $this->route('offer');
        
        if ($routeOffer instanceof CaishhaOffer) {
            $offer = $routeOffer;
        } else {
            $offer = CaishhaOffer::find($routeOffer);
        }

        if (!$offer) {
            return false;
        }

        // Only the offer owner can update their offer
        return $offer->user_id === auth()->id();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'price' => 'sometimes|numeric|min:1|max:999999999',
            'comment' => 'nullable|string|max:500',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Support both route model binding and raw id parameter
            $routeOffer = $this->route('offer');
            
            if ($routeOffer instanceof CaishhaOffer) {
                $offer = $routeOffer;
                if (!$offer->relationLoaded('caishhaAd')) {
                    $offer->load('caishhaAd.ad');
                }
            } else {
                $offer = CaishhaOffer::with('caishhaAd.ad')->find($routeOffer);
            }

            if (!$offer) {
                $validator->errors()->add('offer', 'The offer was not found.');
                return;
            }

            // Cannot update if offer is already accepted or rejected
            if (!$offer->isPending()) {
                $validator->errors()->add('offer', 'Only pending offers can be updated.');
                return;
            }

            // Check if ad still accepts offers
            $caishhaAd = $offer->caishhaAd;
            if (!$caishhaAd || !$caishhaAd->canAcceptOffers()) {
                $validator->errors()->add('offer', 'This ad is no longer accepting offers.');
                return;
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'price.numeric' => 'The offer price must be a valid number.',
            'price.min' => 'The offer price must be at least 1.',
            'price.max' => 'The offer price cannot exceed 999,999,999.',
            'comment.max' => 'The comment cannot exceed 500 characters.',
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
            'message' => 'Cannot update offer',
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
            'errors' => ['authorization' => ['You can only update your own offers']]
        ], 403));
    }
}
