<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDatabaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Database::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                'max:64',
                'regex:/^[a-zA-Z0-9_-]{1,64}$/',
                'unique:databases,name',
            ],
            'engine' => [
                'sometimes',
                'required',
                'string',
                'in:InnoDB,MyISAM',
            ],
            'collation' => [
                'sometimes',
                'required',
                'string',
            ],
            'max_connections' => [
                'sometimes',
                'required',
                'integer',
                'min:10',
                'max:10000',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Database name is required.',
            'name.regex' => 'Database name format is invalid. Use alphanumeric, underscore, or dash.',
            'name.unique' => 'Database name already exists.',
            'engine.in' => 'Selected database engine is invalid.',
            'max_connections.integer' => 'Max connections must be a number.',
            'max_connections.min' => 'Max connections must be at least 10.',
            'max_connections.max' => 'Max connections cannot exceed 10000.',
        ];
    }
}
