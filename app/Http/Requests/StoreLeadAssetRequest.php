<?php

namespace App\Http\Requests;

use App\Models\LeadAsset;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Everyone with lead access may upload (developers included).
        return $this->user()?->hasAnyRole([
            User::ROLE_SUPER_ADMIN,
            User::ROLE_LEADS_ADMIN,
            User::ROLE_SALES,
            User::ROLE_DEVELOPER,
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
            'file_type' => ['required', Rule::in(array_keys(LeadAsset::TYPES))],
            'files'     => ['required', 'array', 'max:20'],
            // Images and common document formats, up to 10 MB each. SVG is
            // intentionally excluded — it can carry embedded scripts (stored XSS).
            // `mimes` validates the detected MIME type; `extensions` additionally
            // pins the client filename extension so a renamed executable is
            // rejected even if its content sniffs as an allowed type.
            'files.*'   => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv',
                'extensions:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv',
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
            'files.required'     => 'Please select at least one file to upload.',
            'files.*.max'        => 'Each file may not be larger than 10 MB.',
            'files.*.mimes'      => 'Only images and common document formats are allowed.',
            'files.*.extensions' => 'Only images and common document formats are allowed.',
        ];
    }
}
