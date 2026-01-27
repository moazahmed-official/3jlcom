<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
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

        // Only super_admin can create roles
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
        return [
            'name' => ['required', 'string', 'max:50', 'unique:roles,name', 'regex:/^[a-z_]+$/'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'permissions' => ['required', 'array'],
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
            'name.required' => 'Role name is required.',
            'name.unique' => 'This role name already exists.',
            'name.regex' => 'Role name must contain only lowercase letters and underscores.',
            'permissions.required' => 'At least one permission must be specified.',
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
            'message' => 'You do not have permission to create roles.',
            'errors' => (object) [],
        ], 403));
    }
}