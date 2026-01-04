<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDnsZoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\DnsZone::class);
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
                'unique:dns_zones,domain',
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
            'domain.unique' => 'DNS zone already exists for this domain.',
        ];
    }
}
