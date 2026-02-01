<?php

namespace App\Http\Requests\Report;

use App\Models\Report;
use App\Models\User;
use App\Models\Ad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
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
            'target_type' => ['required', 'string', Rule::in(Report::VALID_TARGET_TYPES)],
            'target_id' => ['required', 'integer', function ($attribute, $value, $fail) {
                $targetType = $this->input('target_type');
                
                // Validate that target exists based on type
                if ($targetType === 'ad' && !Ad::find($value)) {
                    $fail('The selected ad does not exist.');
                } elseif (in_array($targetType, ['user', 'dealer']) && !User::find($value)) {
                    $fail('The selected user does not exist.');
                }
            }],
            'reason' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'target_type.required' => 'Please specify what you are reporting.',
            'target_type.in' => 'Invalid report target type.',
            'target_id.required' => 'Please specify which item you are reporting.',
            'target_id.integer' => 'Invalid target ID.',
            'reason.required' => 'Please provide a reason for the report.',
            'reason.max' => 'Reason cannot exceed 255 characters.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'details.max' => 'Details cannot exceed 2000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check for duplicate report (same user, same target, same reason within 24 hours)
            $targetType = $this->input('target_type');
            $targetId = $this->input('target_id');
            $reason = $this->input('reason');
            $userId = auth()->id();

            $duplicate = Report::where('reported_by_user_id', $userId)
                ->where('target_type', $targetType)
                ->where('target_id', $targetId)
                ->where('reason', $reason)
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add(
                    'reason',
                    'You have already reported this ' . $targetType . ' for the same reason within the last 24 hours.'
                );
            }

            // Prevent self-reporting
            if (in_array($targetType, ['user', 'dealer']) && $targetId == $userId) {
                $validator->errors()->add(
                    'target_id',
                    'You cannot report yourself.'
                );
            }

            // Prevent ad owner from reporting their own ad
            if ($targetType === 'ad') {
                $ad = Ad::find($targetId);
                if ($ad && $ad->user_id == $userId) {
                    $validator->errors()->add(
                        'target_id',
                        'You cannot report your own ad.'
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
