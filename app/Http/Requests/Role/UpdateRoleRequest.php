<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $role = $this->route('role');

        // Must be authenticated and have admin role
        if (! $user) {
            return false;
        }

        // Only super_admin can update roles
        // Prevent modification of system roles
        if (in_array($role->name, ['admin', 'super_admin'])) {
            return false;
        }

        return $user->roles()
            ->where('name', 'super_admin')
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => ['sometimes', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($role->id), 'regex:/^[a-z_]+$/'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required', 'string', 'max:100'],
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
            'name.unique' => 'This role name already exists.',
            'name.regex' => 'Role name must contain only lowercase letters and underscores.',
            'permissions.array' => 'Permissions must be provided as an array.',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        abort(response()->json([
            'status' => 'error',
            'code' => 403,
            'message' => 'You do not have permission to update this role.',
            'errors' => (object) [],
        ], 403));
    }
}