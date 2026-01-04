<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFtpUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\FtpUser::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:1',
                'max:32',
                'regex:/^[a-zA-Z0-9_-]{1,32}$/',
                'unique:ftp_users,username',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'home_dir' => [
                'sometimes',
                'required',
                'string',
                'regex:/^\/[a-zA-Z0-9\/_\-\.]+$/',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'username.required' => 'FTP username is required.',
            'username.regex' => 'FTP username format is invalid.',
            'username.unique' => 'FTP username already exists.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'home_dir.regex' => 'Home directory path format is invalid.',
        ];
    }
}
