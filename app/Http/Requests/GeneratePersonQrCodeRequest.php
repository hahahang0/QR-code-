<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePersonQrCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('person')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'include' => ['sometimes', 'array'],
            'include.*' => ['required', 'string', 'distinct', Rule::in([
                'date_of_birth', 'gender', 'phone', 'email', 'address', 'organization', 'department', 'designation', 'reference_number', 'emergency_contact', 'notes',
            ])],
            'error_correction' => ['required', Rule::in(['Q', 'H'])],
        ];
    }
}
