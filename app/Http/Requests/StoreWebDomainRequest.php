<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebDomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\WebDomain::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'domain' => [
                'required',
                'string',
                'min:4',
                'max:253',
                'regex:/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9]{2,}$/i',
                'unique:web_domains,domain',
            ],
            'root_path' => [
                'required',
                'string',
                'regex:/^\/[a-zA-Z0-9\/_\-\.]+$/',
            ],
            'php_version' => [
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
            'domain.required' => 'Domain name is required.',
            'domain.regex' => 'Domain name format is invalid.',
            'domain.unique' => 'Domain name already exists.',
            'root_path.required' => 'Root path is required.',
            'root_path.regex' => 'Root path format is invalid.',
            'php_version.required' => 'PHP version is required.',
            'php_version.in' => 'Selected PHP version is invalid.',
            'aliases.*.regex' => 'One or more alias domains have invalid format.',
        ];
    }
}
