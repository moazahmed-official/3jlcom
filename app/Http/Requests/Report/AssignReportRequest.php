<?php

namespace App\Http\Requests\Report;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AssignReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can assign reports
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'moderator_id' => ['required', 'integer', 'exists:users,id', function ($attribute, $value, $fail) {
                $moderator = User::find($value);
                
                if (!$moderator || !$moderator->hasAnyRole(['moderator', 'admin', 'super-admin'])) {
                    $fail('The selected user must be a moderator or admin.');
                }
            }],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'moderator_id.required' => 'Please specify a moderator to assign.',
            'moderator_id.integer' => 'Invalid moderator ID.',
            'moderator_id.exists' => 'The selected moderator does not exist.',
        ];
    }
}
