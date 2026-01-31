<?php

namespace App\Http\Requests;

use App\Models\Ad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PlaceBidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $ad = $this->route('ad');
        
        // Handle route model binding or raw ID
        if ($ad instanceof Ad) {
            $adModel = $ad;
        } else {
            $adModel = Ad::with('auction')->find($ad);
        }

        if (!$adModel || $adModel->type !== 'auction' || !$adModel->auction) {
            return false;
        }

        // Cannot bid on own auction
        if ($adModel->user_id === auth()->id()) {
            return false;
        }

        // Check if auction is accepting bids
        return $adModel->auction->canAcceptBids();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'price' => 'required|numeric|min:1|max:999999999',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $ad = $this->route('ad');
            
            if ($ad instanceof Ad) {
                $adModel = $ad;
            } else {
                $adModel = Ad::with('auction')->find($ad);
            }

            if (!$adModel || !$adModel->auction) {
                $validator->errors()->add('ad', 'Invalid auction.');
                return;
            }

            $auction = $adModel->auction;

            // Validate bid is high enough
            $minimumBid = $auction->getMinimumNextBid();
            if ($this->price < $minimumBid) {
                $currentHighest = $auction->last_price ?? $auction->start_price ?? 0;
                $validator->errors()->add(
                    'price',
                    "Your bid must be at least " . number_format($minimumBid, 2) . ". " .
                    "Current highest bid is " . number_format($currentHighest, 2) . " " .
                    "with a minimum increment of " . number_format($auction->minimum_bid_increment, 2) . "."
                );
            }

            // Check timing
            if (!$auction->hasStarted()) {
                $validator->errors()->add(
                    'auction',
                    'This auction has not started yet. It starts at ' . $auction->start_time->toDateTimeString() . '.'
                );
            }

            if ($auction->hasEnded()) {
                $validator->errors()->add(
                    'auction',
                    'This auction has ended.'
                );
            }

            // Check auction status
            if ($auction->status !== 'active') {
                $validator->errors()->add(
                    'auction',
                    'This auction is no longer accepting bids.'
                );
            }

            // Check if ad is published
            if ($adModel->status !== 'published') {
                $validator->errors()->add(
                    'ad',
                    'This auction listing is not currently published.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'price.required' => 'A bid amount is required.',
            'price.numeric' => 'The bid amount must be a valid number.',
            'price.min' => 'The bid amount must be at least 1.',
            'price.max' => 'The bid amount is too large.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Bid validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        $ad = $this->route('ad');
        
        if ($ad instanceof Ad) {
            $adModel = $ad;
        } else {
            $adModel = Ad::with('auction')->find($ad);
        }

        $message = 'You are not authorized to bid on this auction.';
        $errors = ['authorization' => [$message]];

        if ($adModel && $adModel->user_id === auth()->id()) {
            $message = 'You cannot bid on your own auction.';
            $errors = ['authorization' => [$message]];
        } elseif ($adModel && $adModel->auction && !$adModel->auction->canAcceptBids()) {
            $message = 'This auction is not currently accepting bids.';
            $errors = ['auction' => [$message]];
        }

        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => $message,
                'errors' => $errors
            ], 403)
        );
    }
}
