<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $review = $this->route('review');
        
        // Only the review owner or admin can update
        return auth()->check() && (
            auth()->id() === $review->user_id ||
            auth()->user()->isAdmin()
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'stars' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'stars.required' => 'Please provide a rating.',
            'stars.integer' => 'Rating must be a number.',
            'stars.min' => 'Rating must be at least 1 star.',
            'stars.max' => 'Rating cannot exceed 5 stars.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'body.max' => 'Review body cannot exceed 1000 characters.',
        ];
    }
}
