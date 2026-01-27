<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SellerVerificationRequest;

class SubmitSellerVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check if user is seller/showroom
        if (!in_array($this->user()->account_type, ['seller', 'showroom'])) {
            return false;
        }

        // Check if user doesn't already have a pending or approved request
        $existingRequest = SellerVerificationRequest::where('user_id', $this->user()->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        return !$existingRequest;
    }

    public function rules(): array
    {
        return [
            'documents' => 'required|array|min:1|max:5',
            'documents.*.type' => 'required|string|in:business_license,tax_certificate,identity_document,address_proof,other',
            'documents.*.url' => 'required|url',
            'documents.*.description' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'documents.required' => 'At least one document is required.',
            'documents.min' => 'At least one document is required.',
            'documents.max' => 'Maximum 5 documents allowed.',
            'documents.*.type.required' => 'Document type is required.',
            'documents.*.type.in' => 'Document type must be one of: business_license, tax_certificate, identity_document, address_proof, other.',
            'documents.*.url.required' => 'Document URL is required.',
            'documents.*.url.url' => 'Document URL must be a valid URL.',
            'documents.*.description.max' => 'Document description cannot exceed 255 characters.',
        ];
    }
}