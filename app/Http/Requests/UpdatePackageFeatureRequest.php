<?php

namespace App\Http\Requests;

class UpdatePackageFeatureRequest extends StorePackageFeatureRequest
{
    /**
     * Get the validation rules that apply to the request.
     * 
     * Same as store but all fields are optional (for partial updates).
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        
        // All fields are optional for updates
        foreach ($rules as $field => $fieldRules) {
            if (is_array($fieldRules)) {
                // Remove 'required' if present and ensure 'sometimes' is there
                $rules[$field] = array_filter($fieldRules, fn($rule) => $rule !== 'required');
                if (!in_array('sometimes', $rules[$field])) {
                    array_unshift($rules[$field], 'sometimes');
                }
            }
        }
        
        return $rules;
    }
}
