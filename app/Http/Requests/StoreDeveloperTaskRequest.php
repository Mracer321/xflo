<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeveloperTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only Super Admin and Leads Admin assign developers to leads.
        return $this->user()?->hasAnyRole([
            User::ROLE_SUPER_ADMIN,
            User::ROLE_LEADS_ADMIN,
        ]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'developer_id' => [
                'required',
                // Must be an existing user who holds the developer role.
                Rule::exists('users', 'id')->where('role', User::ROLE_DEVELOPER),
            ],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'developer_id.required' => 'Please select a developer to assign.',
            'developer_id.exists'   => 'The selected user is not a valid developer.',
        ];
    }
}
