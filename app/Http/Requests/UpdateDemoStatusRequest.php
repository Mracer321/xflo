<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDemoStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Admins always; developers only for leads assigned to them. Sales cannot modify.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_LEADS_ADMIN])) {
            return true;
        }

        $lead = $this->route('lead');

        return $user->isDeveloper()
            && $lead instanceof Lead
            && $lead->developer_id === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Live <-> Offline only; "Deleted" is handled by the admin force-delete action.
            'demo_status'    => ['required', Rule::in([Lead::DEMO_LIVE, Lead::DEMO_OFFLINE])],
            // A reason is required whenever a demo is taken offline.
            'offline_reason' => ['nullable', 'required_if:demo_status,offline', 'string', 'max:2000'],
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
            'offline_reason.required_if' => 'Please provide a reason when taking the demo offline.',
        ];
    }
}
