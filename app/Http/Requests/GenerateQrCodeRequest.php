<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateQrCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'person' => ['required', 'array:full_name,date_of_birth,gender,phone,email,address,organization,department,designation,reference_number,emergency_contact,notes'],
            'person.full_name' => ['required', 'string', 'max:120'],
            'person.date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'person.gender' => ['nullable', 'string', 'max:40'],
            'person.phone' => ['nullable', 'string', 'max:40'],
            'person.email' => ['nullable', 'email', 'max:254'],
            'person.address' => ['nullable', 'string', 'max:500'],
            'person.organization' => ['nullable', 'string', 'max:160'],
            'person.department' => ['nullable', 'string', 'max:120'],
            'person.designation' => ['nullable', 'string', 'max:120'],
            'person.reference_number' => ['nullable', 'string', 'max:80'],
            'person.emergency_contact' => ['nullable', 'string', 'max:80'],
            'person.notes' => ['nullable', 'string', 'max:800'],
            'include' => ['sometimes', 'array'],
            'include.*' => [
                'string',
                'distinct',
                Rule::in([
                    'date_of_birth',
                    'gender',
                    'phone',
                    'email',
                    'address',
                    'organization',
                    'department',
                    'designation',
                    'reference_number',
                    'emergency_contact',
                    'notes',
                ]),
            ],
            'error_correction' => ['required', Rule::in(['Q', 'H'])],
        ];
    }
}
