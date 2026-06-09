<?php

namespace App\Http\Requests;

use App\Models\DeveloperTask;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeveloperTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        // Super Admin and Leads Admin may always update the workflow.
        if ($user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_LEADS_ADMIN])) {
            return true;
        }

        // Otherwise only the developer assigned to this task may update it.
        $task = $this->route('developerTask');

        return $task instanceof DeveloperTask
            && $user->isDeveloper()
            && $task->developer_id === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status'              => ['required', Rule::in(array_keys(DeveloperTask::STATUSES))],
            'notes'               => ['nullable', 'string', 'max:5000'],
            'demo_url'            => ['nullable', 'url', 'max:2048'],
            'deployment_platform' => ['nullable', Rule::in(array_keys(DeveloperTask::PLATFORMS))],
            'deployment_date'     => ['nullable', 'date'],
            // Mandatory reason when marking Offline or Deleted.
            'reason'              => ['nullable', 'required_if:status,offline,deleted', 'string', 'max:2000'],
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
            'reason.required_if' => 'A reason is required when marking the task as Offline or Deleted.',
        ];
    }
}
