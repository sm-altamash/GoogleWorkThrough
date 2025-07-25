<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmailRequest extends FormRequest
{

    public function authorize()
    {
        // Check if user has admin role or appropriate permissions
        return auth()->user() && auth()->user()->hasRole('admin');
    }


    public function rules()
    {
        return [
            'username' => [
                'required',
                'string',
                'max:255',
                'unique:institutional_emails,username'
            ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'department' => 'nullable|string|max:255',
            'org_unit' => 'nullable|string|max:255',
            'force_password_reset' => 'boolean'
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages()
    {
        return [
            'username.unique' => 'An institutional email already exists for this username.',
            'user_id.exists' => 'The specified user does not exist.',
            'username.required' => 'Username is required to create institutional email.'
        ];
    }
}
