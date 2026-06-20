<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadSalesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Sales and admins may update sales/follow-up fields.
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Sales may only set demo-sent, follow-up, converted or rejected.
            'workflow_status' => ['required', Rule::in(Lead::SALES_WORKFLOW_STATUSES)],
            'sales_notes'     => ['nullable', 'string', 'max:5000'],
        ];
    }
}
