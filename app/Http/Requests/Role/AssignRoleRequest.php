<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class AssignRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // Must be authenticated and have admin role
        if (! $user) {
            return false;
        }

        // Check if user has admin or super_admin role
        return $user->roles()
            ->whereIn('name', ['admin', 'super_admin'])
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'At least one role must be specified.',
            'roles.array' => 'Roles must be provided as an array.',
            'roles.min' => 'At least one role must be specified.',
            'roles.*.required' => 'Each role name is required.',
            'roles.*.string' => 'Each role name must be a string.',
            'roles.*.exists' => 'One or more specified roles do not exist.',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization()
    {
        abort(response()->json([
            'status' => 'error',
            'code' => 403,
            'message' => 'You do not have permission to manage user roles.',
            'errors' => (object) [],
        ], 403));
    }
}