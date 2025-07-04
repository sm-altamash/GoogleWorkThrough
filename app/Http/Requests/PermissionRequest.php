<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|unique:permissions',
        ];

        if (in_array($this->method(), ['PUT', 'PATCH'])) {
            $permission = $this->route()->parameter('permission');
            $rules['name'] = 'required|string|unique:permissions,name,' . $permission->id;
        }

        return $rules;
    }
}
