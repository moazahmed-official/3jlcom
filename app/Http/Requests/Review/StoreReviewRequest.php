<?php

namespace App\Http\Requests\Review;

use App\Models\Review;
use App\Models\User;
use App\Models\Ad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_type' => ['required', 'string', Rule::in(['ad', 'seller'])],
            'target_id' => ['required', 'integer', function ($attribute, $value, $fail) {
                $targetType = $this->input('target_type');
                
                if ($targetType === 'ad') {
                    if (!Ad::find($value)) {
                        $fail('The selected ad does not exist.');
                    }
                } elseif ($targetType === 'seller') {
                    if (!User::find($value)) {
                        $fail('The selected seller does not exist.');
                    }
                }
            }],
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'target_type.required' => 'Please specify what you are reviewing (ad or seller).',
            'target_type.in' => 'Target type must be either "ad" or "seller".',
            'target_id.required' => 'Please specify which item you are reviewing.',
            'target_id.integer' => 'Invalid target ID.',
            'stars.required' => 'Please provide a rating.',
            'stars.integer' => 'Rating must be a number.',
            'stars.min' => 'Rating must be at least 1 star.',
            'stars.max' => 'Rating cannot exceed 5 stars.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'body.max' => 'Review body cannot exceed 1000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check for duplicate review
            $targetType = $this->input('target_type');
            $targetId = $this->input('target_id');
            $userId = auth()->id();

            $query = Review::where('user_id', $userId);

            if ($targetType === 'ad') {
                $query->where('ad_id', $targetId);
            } elseif ($targetType === 'seller') {
                $query->where('seller_id', $targetId);
            }

            if ($query->exists()) {
                $validator->errors()->add(
                    'target_id',
                    'You have already reviewed this ' . $targetType . '.'
                );
            }

            // Prevent self-review for sellers
            if ($targetType === 'seller' && $targetId == $userId) {
                $validator->errors()->add(
                    'target_id',
                    'You cannot review yourself.'
                );
            }

            // Prevent ad owner from reviewing their own ad
            if ($targetType === 'ad') {
                $ad = Ad::find($targetId);
                if ($ad && $ad->user_id == $userId) {
                    $validator->errors()->add(
                        'target_id',
                        'You cannot review your own ad.'
                    );
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize target_type to lowercase
        if ($this->has('target_type')) {
            $this->merge([
                'target_type' => strtolower($this->input('target_type')),
            ]);
        }
    }
}
