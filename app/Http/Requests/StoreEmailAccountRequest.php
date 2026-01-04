<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\EmailAccount::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'unique:email_accounts,email',
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                \Illuminate\Validation\Rules\Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'quota_mb' => [
                'required',
                'integer',
                'min:256',
                'max:102400',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Email address format is invalid.',
            'email.unique' => 'Email address already exists.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'quota_mb.required' => 'Quota is required.',
            'quota_mb.integer' => 'Quota must be a number.',
            'quota_mb.min' => 'Quota must be at least 256 MB.',
            'quota_mb.max' => 'Quota cannot exceed 100 GB.',
        ];
    }
}
