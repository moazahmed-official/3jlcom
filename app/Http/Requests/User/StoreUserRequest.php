<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only admin users can create new users.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // Must be authenticated and have admin role
        if (! $user) {
            return false;
        }

        // Check if user has admin or super_admin role
        // Using the roles relationship from the User model
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50', 'unique:users,phone'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'account_type' => ['sometimes', 'nullable', 'string', 'in:individual,dealer,showroom,marketer,moderator,country_manager'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
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
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.required' => 'The phone field is required.',
            'phone.unique' => 'This phone number is already registered.',
            'country_id.required' => 'The country field is required.',
            'country_id.exists' => 'The selected country does not exist.',
            'account_type.in' => 'The selected account type is invalid.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
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
            'message' => 'You do not have permission to create users.',
            'errors' => (object) [],
        ], 403));
    }
}
