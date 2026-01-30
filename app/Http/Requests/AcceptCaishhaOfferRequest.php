<?php

namespace App\Http\Requests;

use App\Models\Ad;
use App\Models\CaishhaOffer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AcceptCaishhaOfferRequest extends FormRequest
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
            if (!$ad->relationLoaded('caishhaAd')) {
                $ad->load('caishhaAd');
            }
        } else {
            $ad = Ad::with('caishhaAd')->find($routeAd);
        }
        
        if (!$ad || $ad->type !== 'caishha') {
            return false;
        }

        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Only the ad owner or admin can accept offers
        return $ad->user_id === $user->id || (method_exists($user, 'isAdmin') && $user->isAdmin());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // No body fields required - offer ID comes from URL
        return [];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Support both route model binding and raw id parameter
            $routeAd = $this->route('ad');
            
            if ($routeAd instanceof Ad) {
                $ad = $routeAd;
                if (!$ad->relationLoaded('caishhaAd')) {
                    $ad->load('caishhaAd.offers');
                }
            } else {
                $ad = Ad::with('caishhaAd.offers')->find($routeAd);
            }
            
            $routeOffer = $this->route('offer');
            $offerId = $routeOffer instanceof CaishhaOffer ? $routeOffer->id : $routeOffer;

            if (!$ad) {
                $validator->errors()->add('ad', 'The Caishha ad was not found.');
                return;
            }

            if ($ad->type !== 'caishha') {
                $validator->errors()->add('ad', 'This is not a Caishha ad.');
                return;
            }

            $caishhaAd = $ad->caishhaAd;
            if (!$caishhaAd) {
                $validator->errors()->add('ad', 'Caishha ad details not found.');
                return;
            }

            // Check if there's already an accepted offer
            if ($caishhaAd->acceptedOffer()) {
                $validator->errors()->add('offer', 'An offer has already been accepted for this ad.');
                return;
            }

            // Find the specific offer
            $offer = CaishhaOffer::where('id', $offerId)
                ->where('ad_id', $ad->id)
                ->first();

            if (!$offer) {
                $validator->errors()->add('offer', 'The specified offer was not found.');
                return;
            }

            if (!$offer->isPending()) {
                $validator->errors()->add('offer', 'This offer cannot be accepted because it is not pending.');
                return;
            }

            // Check visibility period (unless admin)
            if (!auth()->user()->isAdmin()) {
                if (!$caishhaAd->areOffersVisibleToSeller()) {
                    $endsAt = $caishhaAd->getVisibilityPeriodEndsAt();
                    $message = 'You cannot accept offers yet.';
                    if ($endsAt) {
                        $message .= ' Offers will be visible after ' . $endsAt->format('Y-m-d H:i:s') . '.';
                    }
                    $validator->errors()->add('visibility', $message);
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
            'message' => 'Cannot accept offer',
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
            'errors' => ['authorization' => ['Only the ad owner can accept offers']]
        ], 403));
    }
}
