<?php

namespace App\Http\Requests;

use App\Models\Ad;
use App\Models\CaishhaAd;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmitCaishhaOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

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
        
        // Load relationships if not already loaded
        if (!$ad->relationLoaded('caishhaAd')) {
            $ad->load('caishhaAd.offers');
        }
        
        if ($ad->type !== 'caishha' || !$ad->caishhaAd) {
            return false;
        }

        // Cannot submit offer on own ad
        if ($ad->user_id === auth()->id()) {
            return false;
        }

        // Check window timing using user's method
        return auth()->user()->canSubmitCaishhaOffer($ad->caishhaAd);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'price' => 'required|numeric|min:1|max:999999999',
            'comment' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'price.required' => 'Please enter your offer price.',
            'price.numeric' => 'The offer price must be a valid number.',
            'price.min' => 'The offer price must be greater than 0.',
            'price.max' => 'The offer price is too high.',
            'comment.max' => 'The comment cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'price' => 'offer price',
            'comment' => 'offer comment',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Support both route model binding and raw id parameter named 'ad'
            $routeAd = $this->route('ad');
            
            if ($routeAd instanceof Ad) {
                $ad = $routeAd;
                if (!$ad->relationLoaded('caishhaAd')) {
                    $ad->load('caishhaAd.offers');
                }
            } else {
                $ad = Ad::with('caishhaAd.offers')->find($routeAd);
            }
            
            if (!$ad) {
                $validator->errors()->add('ad', 'The Caishha ad was not found.');
                return;
            }

            if ($ad->type !== 'caishha') {
                $validator->errors()->add('ad', 'This is not a Caishha ad.');
                return;
            }

            if ($ad->status !== 'published') {
                $validator->errors()->add('ad', 'This ad is not accepting offers.');
                return;
            }

            $caishhaAd = $ad->caishhaAd;
            if (!$caishhaAd) {
                $validator->errors()->add('ad', 'Caishha ad details not found.');
                return;
            }

            // Check if there's already an accepted offer
            if ($caishhaAd->acceptedOffer()) {
                $validator->errors()->add('ad', 'This ad has already accepted an offer and is no longer available.');
                return;
            }

            // Check if user already has a pending offer
            $existingOffer = $caishhaAd->offers()
                ->where('user_id', auth()->id())
                ->pending()
                ->first();

            if ($existingOffer) {
                $validator->errors()->add(
                    'offer',
                    'You already have a pending offer on this ad. Please update your existing offer instead.'
                );
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
        // Support both route model binding and raw id parameter named 'ad'
        $routeAd = $this->route('ad');
        
        if ($routeAd instanceof Ad) {
            $ad = $routeAd;
            if (!$ad->relationLoaded('caishhaAd')) {
                $ad->load('caishhaAd');
            }
        } else {
            $ad = Ad::with('caishhaAd')->find($routeAd);
        }
        
        if (!$ad || $ad->type !== 'caishha') {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Caishha ad not found',
                'errors' => ['ad' => ['The requested Caishha ad does not exist']]
            ], 404));
        }

        if ($ad->user_id === auth()->id()) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Cannot submit offer on your own ad',
                'errors' => ['authorization' => ['You cannot submit an offer on your own Caishha ad']]
            ], 403));
        }

        // Check timing
        $caishhaAd = $ad->caishhaAd;
        $user = auth()->user();

        if ($caishhaAd && $caishhaAd->isInDealerWindow() && !$user->isDealerOrShowroom()) {
            $endsAt = $caishhaAd->getDealerWindowEndsAt();
            $message = 'This ad is currently in the dealer-exclusive window.';
            if ($endsAt) {
                $message .= ' You can submit offers after ' . $endsAt->format('Y-m-d H:i:s') . '.';
            }
            
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => $message,
                'errors' => ['authorization' => [$message]]
            ], 403));
        }

        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 403,
            'message' => 'You are not authorized to submit an offer on this ad',
            'errors' => ['authorization' => ['Offer submission is not available at this time']]
        ], 403));
    }
}
