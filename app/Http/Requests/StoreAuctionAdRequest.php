<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAuctionAdRequest extends FormRequest
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

        return [
            // Base ad fields
            'title' => 'required|string|min:5|max:255',
            'description' => 'required|string|min:10|max:2000',
            'category_id' => 'required|integer|exists:categories,id',
            'brand_id' => 'required|integer|exists:brands,id',
            'model_id' => 'required|integer|exists:models,id',
            'city_id' => 'required|integer|exists:cities,id',
            'country_id' => 'required|integer|exists:countries,id',
            'year' => "required|integer|min:1900|max:{$currentYear}",
            'contact_phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'media_ids' => 'nullable|array|max:10',
            'media_ids.*' => 'integer|exists:media,id',
            'period_days' => 'nullable|integer|min:1|max:365',
            
            // Auction-specific fields
            'start_price' => 'nullable|numeric|min:0|max:999999999',
            'reserve_price' => 'nullable|numeric|min:0|max:999999999|gte:start_price',
            'start_time' => 'required|date|after_or_equal:now',
            'end_time' => 'required|date|after:start_time',
            'minimum_bid_increment' => 'nullable|numeric|min:1|max:1000000',
            'auto_close' => 'nullable|boolean',
            'is_last_price_visible' => 'nullable|boolean',
            'anti_snip_window_seconds' => 'nullable|integer|min:60|max:3600',
            'anti_snip_extension_seconds' => 'nullable|integer|min:60|max:3600',
            
            // Admin can create for other users
            'user_id' => 'nullable|integer|exists:users,id',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validate that auction duration is at least 1 hour
            if ($this->start_time && $this->end_time) {
                $startTime = \Carbon\Carbon::parse($this->start_time);
                $endTime = \Carbon\Carbon::parse($this->end_time);
                
                $durationHours = $startTime->diffInHours($endTime);
                if ($durationHours < 1) {
                    $validator->errors()->add(
                        'end_time',
                        'Auction duration must be at least 1 hour.'
                    );
                }

                // Maximum auction duration is 30 days
                $durationDays = $startTime->diffInDays($endTime);
                if ($durationDays > 30) {
                    $validator->errors()->add(
                        'end_time',
                        'Auction duration cannot exceed 30 days.'
                    );
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The auction title is required.',
            'title.min' => 'The auction title must be at least 5 characters.',
            'title.max' => 'The auction title cannot exceed 255 characters.',
            'description.required' => 'The auction description is required.',
            'description.min' => 'The auction description must be at least 10 characters.',
            'description.max' => 'The auction description cannot exceed 2000 characters.',
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
            'start_price.min' => 'The starting price cannot be negative.',
            'start_price.max' => 'The starting price is too large.',
            'reserve_price.gte' => 'The reserve price must be greater than or equal to the starting price.',
            'start_time.required' => 'The auction start time is required.',
            'start_time.after_or_equal' => 'The auction start time must be now or in the future.',
            'end_time.required' => 'The auction end time is required.',
            'end_time.after' => 'The auction end time must be after the start time.',
            'minimum_bid_increment.min' => 'The minimum bid increment must be at least 1.',
            'media_ids.max' => 'You can attach up to 10 media files.',
            'media_ids.*.exists' => 'One or more selected media files are invalid.',
            'period_days.min' => 'The ad period must be at least 1 day.',
            'period_days.max' => 'The ad period cannot exceed 365 days.',
            'anti_snip_window_seconds.min' => 'Anti-snipe window must be at least 60 seconds.',
            'anti_snip_window_seconds.max' => 'Anti-snipe window cannot exceed 1 hour.',
            'anti_snip_extension_seconds.min' => 'Anti-snipe extension must be at least 60 seconds.',
            'anti_snip_extension_seconds.max' => 'Anti-snipe extension cannot exceed 1 hour.',
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
            'start_price' => 'starting price',
            'reserve_price' => 'reserve price',
            'start_time' => 'start time',
            'end_time' => 'end time',
            'minimum_bid_increment' => 'minimum bid increment',
            'media_ids' => 'media files',
            'period_days' => 'ad duration',
            'anti_snip_window_seconds' => 'anti-snipe window',
            'anti_snip_extension_seconds' => 'anti-snipe extension',
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
}
