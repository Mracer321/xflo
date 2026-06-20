<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadDemoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Developers may only update demo fields on leads assigned to them;
     * Super Admin has full access.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
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
            // Developers may only move the workflow to demo-in-progress or demo-ready.
            'workflow_status' => ['required', Rule::in(Lead::DEV_WORKFLOW_STATUSES)],
            'demo_url'        => ['nullable', 'url', 'max:2048'],
            'demo_notes'      => ['nullable', 'string', 'max:5000'],
        ];
    }
}
