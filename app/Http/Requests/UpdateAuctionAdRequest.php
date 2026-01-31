<?php

namespace App\Http\Requests;

use App\Models\Ad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAuctionAdRequest extends FormRequest
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
            $adModel = Ad::find($ad);
        }

        if (!$adModel) {
            return false;
        }

        // Owner or admin can update
        return $adModel->user_id === auth()->id() || auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $currentYear = (int) date('Y');

        return [
            // Base ad fields (all optional for updates)
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
            
            // Auction-specific fields
            'start_price' => 'sometimes|numeric|min:0|max:999999999',
            'reserve_price' => 'nullable|numeric|min:0|max:999999999',
            'start_time' => 'sometimes|date|after_or_equal:now',
            'end_time' => 'sometimes|date',
            'minimum_bid_increment' => 'nullable|numeric|min:1|max:1000000',
            'auto_close' => 'nullable|boolean',
            'is_last_price_visible' => 'nullable|boolean',
            'anti_snip_window_seconds' => 'nullable|integer|min:60|max:3600',
            'anti_snip_extension_seconds' => 'nullable|integer|min:60|max:3600',
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
                return;
            }

            $auction = $adModel->auction;

            // Check if auction has bids - cannot update most fields if bids exist
            if ($auction->bid_count > 0) {
                $restrictedFields = [
                    'start_price',
                    'start_time',
                    'minimum_bid_increment',
                ];

                foreach ($restrictedFields as $field) {
                    if ($this->has($field)) {
                        $validator->errors()->add(
                            $field,
                            "Cannot update {$field} - auction already has bids."
                        );
                    }
                }
            }

            // Validate end_time is after start_time
            if ($this->has('end_time')) {
                $startTime = $this->has('start_time') 
                    ? \Carbon\Carbon::parse($this->start_time) 
                    : $auction->start_time;
                $endTime = \Carbon\Carbon::parse($this->end_time);

                if ($startTime && $endTime->lessThanOrEqualTo($startTime)) {
                    $validator->errors()->add(
                        'end_time',
                        'The auction end time must be after the start time.'
                    );
                }

                // Auction duration must be at least 1 hour
                if ($startTime && $endTime->diffInHours($startTime) < 1) {
                    $validator->errors()->add(
                        'end_time',
                        'Auction duration must be at least 1 hour.'
                    );
                }

                // Cannot shorten an active auction with bids
                if ($auction->bid_count > 0 && $endTime->lessThan($auction->end_time)) {
                    $validator->errors()->add(
                        'end_time',
                        'Cannot shorten auction duration when bids exist.'
                    );
                }
            }

            // Reserve price must be >= start_price
            if ($this->has('reserve_price') && $this->reserve_price !== null) {
                $startPrice = $this->has('start_price') ? $this->start_price : $auction->start_price;
                if ($startPrice && $this->reserve_price < $startPrice) {
                    $validator->errors()->add(
                        'reserve_price',
                        'The reserve price must be greater than or equal to the starting price.'
                    );
                }
            }

            // Cannot update closed or cancelled auction
            if (in_array($auction->status, ['closed', 'cancelled'])) {
                $validator->errors()->add(
                    'auction',
                    'Cannot update a closed or cancelled auction.'
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
            'title.min' => 'The auction title must be at least 5 characters.',
            'title.max' => 'The auction title cannot exceed 255 characters.',
            'description.min' => 'The auction description must be at least 10 characters.',
            'description.max' => 'The auction description cannot exceed 2000 characters.',
            'category_id.exists' => 'The selected category is invalid.',
            'brand_id.exists' => 'The selected brand is invalid.',
            'model_id.exists' => 'The selected model is invalid.',
            'city_id.exists' => 'The selected city is invalid.',
            'country_id.exists' => 'The selected country is invalid.',
            'year.min' => 'The vehicle year must be 1900 or later.',
            'year.max' => 'The vehicle year cannot be in the future.',
            'start_price.min' => 'The starting price cannot be negative.',
            'start_time.after_or_equal' => 'The auction start time must be now or in the future.',
            'end_time.after' => 'The auction end time must be after the start time.',
            'media_ids.max' => 'You can attach up to 10 media files.',
            'media_ids.*.exists' => 'One or more selected media files are invalid.',
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
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to update this auction']]
            ], 403)
        );
    }
}
