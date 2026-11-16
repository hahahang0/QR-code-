<?php

namespace App\Http\Requests;

use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'gender' => ['nullable', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:254'],
            'address' => ['nullable', 'string', 'max:500'],
            'organization' => ['nullable', 'string', 'max:160'],
            'department' => ['nullable', 'string', 'max:120'],
            'designation' => ['nullable', 'string', 'max:120'],
            'reference_number' => ['nullable', 'string', 'max:80', Rule::unique('people', 'reference_number')->ignore($this->route('person') instanceof Person ? $this->route('person')->id : null)],
            'emergency_contact' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:800'],
            'status' => ['required', Rule::in(['active', 'archived'])],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'qr_include' => ['sometimes', 'array'],
            'qr_include.*' => ['required', 'string', 'distinct', Rule::in([
                'date_of_birth', 'gender', 'phone', 'email', 'address', 'organization', 'department', 'designation', 'reference_number', 'emergency_contact', 'notes',
            ])],
            'qr_error_correction' => ['sometimes', Rule::in(['Q', 'H'])],
            'generate_qr' => ['sometimes', 'boolean'],
            'qr_include_present' => ['sometimes', 'boolean'],
        ];
    }
}
