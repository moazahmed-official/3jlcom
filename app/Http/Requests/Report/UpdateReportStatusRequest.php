<?php

namespace App\Http\Requests\Report;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        $report = $this->route('report');

        // Admin or moderator can update status
        if ($user->hasAnyRole(['admin', 'super-admin', 'moderator'])) {
            return true;
        }

        // Assigned moderator can update status
        if ($report->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Report::VALID_STATUSES)],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Please specify the new status.',
            'status.in' => 'Invalid status. Must be one of: ' . implode(', ', Report::VALID_STATUSES),
            'message.max' => 'Message cannot exceed 1000 characters.',
        ];
    }
}
