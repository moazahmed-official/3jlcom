<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNormalAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // Only admins can specify user_id for creating ads for other users
                    if ($value && $value !== auth()->id() && !auth()->user()->isAdmin()) {
                        $fail('Only admins can create ads for other users.');
                    }
                }
            ],
            'title' => [
                'required',
                'string',
                'max:255',
                'min:5'
            ],
            'description' => [
                'required',
                'string',
                'min:10',
                'max:2000'
            ],
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id'
            ],
            'city_id' => [
                'required',
                'integer',
                'exists:cities,id'
            ],
            'country_id' => [
                'required',
                'integer',
                'exists:countries,id'
            ],
            'brand_id' => [
                'nullable',
                'integer',
                'exists:brands,id'
            ],
            'model_id' => [
                'nullable',
                'integer',
                'exists:models,id'
            ],
            'year' => [
                'nullable',
                'integer',
                'min:1900',
                'max:' . (date('Y') + 1)
            ],
            'color' => [
                'nullable',
                'string',
                'max:100'
            ],
            'millage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999'
            ],
            'price_cash' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999'
            ],
            'media_ids' => [
                'nullable',
                'array',
                'max:10'
            ],
            'media_ids.*' => [
                'integer',
                'exists:media,id'
            ],
            'contact_phone' => [
                'nullable',
                'string',
                'regex:/^[+]?[0-9\s\-\(\)]+$/'
            ],
            'whatsapp_number' => [
                'nullable',
                'string',
                'regex:/^[+]?[0-9\s\-\(\)]+$/'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Ad title is required.',
            'title.min' => 'Ad title must be at least 5 characters.',
            'description.required' => 'Ad description is required.',
            'description.min' => 'Ad description must be at least 10 characters.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'city_id.required' => 'City is required.',
            'city_id.exists' => 'Selected city does not exist.',
            'country_id.required' => 'Country is required.',
            'country_id.exists' => 'Selected country does not exist.',
            'brand_id.exists' => 'Selected brand does not exist.',
            'model_id.exists' => 'Selected model does not exist.',
            'year.min' => 'Year cannot be before 1900.',
            'year.max' => 'Year cannot be in the future.',
            'price_cash.min' => 'Price cannot be negative.',
            'price_cash.max' => 'Price is too high.',
            'media_ids.max' => 'You can upload a maximum of 10 media files.',
            'media_ids.*.exists' => 'One or more selected media files do not exist.',
            'contact_phone.regex' => 'Invalid phone number format.',
            'whatsapp_number.regex' => 'Invalid WhatsApp number format.'
        ];
    }

    protected function prepareForValidation()
    {
        // Ensure media belongs to current user
        if ($this->has('media_ids') && is_array($this->media_ids)) {
            $userMediaIds = \App\Models\Media::where('user_id', auth()->id())
                ->whereIn('id', $this->media_ids)
                ->pluck('id')
                ->toArray();
            
            $this->merge([
                'media_ids' => $userMediaIds
            ]);
        }
    }
}