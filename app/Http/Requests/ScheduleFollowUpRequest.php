<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleFollowUpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Sales and admins may schedule follow-ups.
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'next_follow_up_at' => ['required', 'date', 'after_or_equal:today'],
            'follow_up_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
