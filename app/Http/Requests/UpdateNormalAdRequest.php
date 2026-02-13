<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNormalAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'string',
                'max:255',
                'min:5'
            ],
            'description' => [
                'sometimes',
                'string',
                'min:10',
                'max:2000'
            ],
            'brand_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:brands,id'
            ],
            'model_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:models,id'
            ],
            'year' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1900',
                'max:' . (date('Y') + 1)
            ],
            'color' => [
                'sometimes',
                'nullable',
                'string',
                'max:100'
            ],
            'millage' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:9999999'
            ],
            'price_cash' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999999'
            ],
            'status' => [
                'sometimes',
                'string',
                'in:draft,pending,published,expired,removed'
            ],
            'media_ids' => [
                'sometimes',
                'nullable',
                'array',
                'max:10'
            ],
            'media_ids.*' => [
                'integer',
                'exists:media,id'
            ],
            'contact_phone' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^[+]?[0-9\s\-\(\)]+$/'
            ],
            'whatsapp_number' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^[+]?[0-9\s\-\(\)]+$/'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'Ad title must be at least 5 characters.',
            'description.min' => 'Ad description must be at least 10 characters.',
            'brand_id.exists' => 'Selected brand does not exist.',
            'model_id.exists' => 'Selected model does not exist.',
            'year.min' => 'Year cannot be before 1900.',
            'year.max' => 'Year cannot be in the future.',
            'price_cash.min' => 'Price cannot be negative.',
            'price_cash.max' => 'Price is too high.',
            'status.in' => 'Status must be one of: draft, pending, published, expired, removed.',
            'media_ids.max' => 'You can upload a maximum of 10 media files.',
            'media_ids.*.exists' => 'One or more selected media files do not exist.',
            'contact_phone.regex' => 'Invalid phone number format.',
            'whatsapp_number.regex' => 'Invalid WhatsApp number format.'
        ];
    }

    protected function prepareForValidation()
    {
        // Ensure media belongs to current user or admin
        if ($this->has('media_ids') && is_array($this->media_ids)) {
            $query = \App\Models\Media::whereIn('id', $this->media_ids);
            
            // Admin can use any media
            if (!auth()->user()->hasRole('admin')) {
                $query->where('user_id', auth()->id());
            }
            
            $userMediaIds = $query->pluck('id')->toArray();
            
            $this->merge([
                'media_ids' => $userMediaIds
            ]);
        }
    }
}