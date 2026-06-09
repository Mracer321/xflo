<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([
            User::ROLE_SUPER_ADMIN,
            User::ROLE_LEADS_ADMIN,
            User::ROLE_SALES,
        ]) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Unchecked checkbox is absent from the payload; normalise to a boolean.
        $this->merge([
            'website_exists' => $this->boolean('website_exists'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_name'       => ['required', 'string', 'max:255'],
            'owner_name'          => ['nullable', 'string', 'max:255'],
            'mobile_number'       => ['nullable', 'string', 'max:30'],
            'whatsapp_number'     => ['nullable', 'string', 'max:30'],
            'email'               => ['nullable', 'email', 'max:255'],
            'category'            => ['nullable', 'string', 'max:255'],
            'address'             => ['nullable', 'string', 'max:1000'],
            'google_business_url' => ['nullable', 'url', 'max:2048'],
            'website_exists'      => ['required', 'boolean'],
            'facebook_url'        => ['nullable', 'url', 'max:2048'],
            'instagram_url'       => ['nullable', 'url', 'max:2048'],
            'status'              => ['required', Rule::in(array_keys(Lead::STATUSES))],
            'notes'               => ['nullable', 'string', 'max:5000'],
        ];
    }
}
