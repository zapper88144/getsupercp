<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebDomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->webDomain);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'root_path' => [
                'sometimes',
                'required',
                'string',
                'regex:/^\/[a-zA-Z0-9\/_\-\.]+$/',
            ],
            'php_version' => [
                'sometimes',
                'required',
                'string',
                'in:7.4,8.0,8.1,8.2,8.3,8.4',
            ],
            'ssl_enabled' => [
                'nullable',
                'boolean',
            ],
            'aliases' => [
                'nullable',
                'array',
            ],
            'aliases.*' => [
                'string',
                'regex:/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9]{2,}$/i',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'root_path.regex' => 'Root path format is invalid.',
            'php_version.in' => 'Selected PHP version is invalid.',
            'aliases.*.regex' => 'One or more alias domains have invalid format.',
        ];
    }
}
