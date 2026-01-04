<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'role' => [
                'required',
                'string',
                Rule::in(['super-admin', 'admin', 'moderator', 'user']),
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['active', 'suspended', 'inactive']),
            ],
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The user name is required',
            'email.required' => 'The email address is required',
            'email.unique' => 'This email address is already in use',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'role.required' => 'A role must be selected',
            'role.in' => 'The selected role is invalid',
            'status.required' => 'A status must be selected',
            'status.in' => 'The selected status is invalid',
        ];
    }
}
