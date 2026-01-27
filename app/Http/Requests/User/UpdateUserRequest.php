<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $targetUser = $this->route('user');

        // Must be authenticated
        if (! $user) {
            return false;
        }

        // Users can update their own profile
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Admins and super_admins can update any user
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
        $targetUser = $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($targetUser->id)],
            'phone' => ['sometimes', 'string', 'max:50', Rule::unique('users', 'phone')->ignore($targetUser->id)],
            'country_id' => ['sometimes', 'integer', 'exists:countries,id'],
            'city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'account_type' => ['sometimes', 'string', 'in:individual,dealer,showroom,marketer,moderator,country_manager,admin,business'],
            'profile_image_id' => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
            'password' => ['sometimes', 'string', 'min:8', 'max:72'],
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
            'name.string' => 'The name must be a valid string.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.unique' => 'This phone number is already registered.',
            'country_id.exists' => 'The selected country does not exist.',
            'city_id.exists' => 'The selected city does not exist.',
            'account_type.in' => 'The selected account type is invalid.',
            'profile_image_id.exists' => 'The selected profile image does not exist.',
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
            'message' => 'You do not have permission to update this user.',
            'errors' => (object) [],
        ], 403));
    }
}