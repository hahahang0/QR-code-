<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ManagePersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['employment', 'education', 'skills', 'projects', 'certifications', 'other_social_links'] as $section) {
            $rows = $this->input($section, []);

            if (! is_array($rows)) {
                continue;
            }

            $rows = array_values(array_filter($rows, function (mixed $row): bool {
                if (! is_array($row)) {
                    return false;
                }

                return collect($row)->contains(fn (mixed $value): bool => $value instanceof UploadedFile || (is_scalar($value) && trim((string) $value) !== ''));
            }));

            $this->merge([$section => $rows]);
        }
    }

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
            'designation' => ['nullable', 'string', 'max:160'],
            'reference_number' => ['nullable', 'string', 'max:80', Rule::unique('people', 'reference_number')->ignore($this->route('person')?->id)],
            'emergency_contact' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:800'],
            'status' => ['sometimes', 'in:active,archived'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            'professional_bio' => ['nullable', 'string', 'max:4000'],
            'location' => ['nullable', 'string', 'max:160'],
            'website_url' => ['nullable', 'url:http,https', 'max:2048'],
            'linkedin_url' => ['nullable', 'url:http,https', 'max:2048'],
            'github_url' => ['nullable', 'url:http,https', 'max:2048'],
            'other_social_links' => ['nullable', 'array', 'max:8'],
            'other_social_links.*' => ['required', 'array:label,url'],
            'other_social_links.*.label' => ['required', 'string', 'max:60'],
            'other_social_links.*.url' => ['required', 'url:http,https', 'max:2048'],
            'employment' => ['nullable', 'array', 'max:25'],
            'employment.*' => ['required', 'array:id,company_name,job_title,employment_type,start_date,end_date,location,description,is_current,company_logo'],
            'employment.*.id' => ['nullable', 'integer'],
            'employment.*.company_name' => ['required', 'string', 'max:160'],
            'employment.*.job_title' => ['required', 'string', 'max:160'],
            'employment.*.employment_type' => ['nullable', 'string', 'max:60'],
            'employment.*.start_date' => ['nullable', 'date_format:Y-m-d'],
            'employment.*.end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:employment.*.start_date'],
            'employment.*.location' => ['nullable', 'string', 'max:160'],
            'employment.*.description' => ['nullable', 'string', 'max:4000'],
            'employment.*.is_current' => ['sometimes', 'boolean'],
            'employment.*.company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            'education' => ['nullable', 'array', 'max:20'],
            'education.*' => ['required', 'array:id,institution_name,qualification,field_of_study,start_year,graduation_year,description'],
            'education.*.id' => ['nullable', 'integer'],
            'education.*.institution_name' => ['required', 'string', 'max:180'],
            'education.*.qualification' => ['nullable', 'string', 'max:160'],
            'education.*.field_of_study' => ['nullable', 'string', 'max:160'],
            'education.*.start_year' => ['nullable', 'integer', 'between:1900,2100'],
            'education.*.graduation_year' => ['nullable', 'integer', 'between:1900,2100'],
            'education.*.description' => ['nullable', 'string', 'max:4000'],
            'skills' => ['nullable', 'array', 'max:40'],
            'skills.*' => ['required', 'array:id,name'],
            'skills.*.id' => ['nullable', 'integer'],
            'skills.*.name' => ['required', 'string', 'max:80', 'distinct:ignore_case'],
            'projects' => ['nullable', 'array', 'max:25'],
            'projects.*' => ['required', 'array:id,title,image,description,technologies,project_url,github_url,start_date,completion_date'],
            'projects.*.id' => ['nullable', 'integer'],
            'projects.*.title' => ['required', 'string', 'max:180'],
            'projects.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            'projects.*.description' => ['nullable', 'string', 'max:5000'],
            'projects.*.technologies' => ['nullable', 'string', 'max:500'],
            'projects.*.project_url' => ['nullable', 'url:http,https', 'max:2048'],
            'projects.*.github_url' => ['nullable', 'url:http,https', 'max:2048'],
            'projects.*.start_date' => ['nullable', 'date_format:Y-m-d'],
            'projects.*.completion_date' => ['nullable', 'date_format:Y-m-d'],
            'certifications' => ['nullable', 'array', 'max:25'],
            'certifications.*' => ['required', 'array:id,name,issuer,issue_date,credential_url,image,description'],
            'certifications.*.id' => ['nullable', 'integer'],
            'certifications.*.name' => ['required', 'string', 'max:180'],
            'certifications.*.issuer' => ['nullable', 'string', 'max:180'],
            'certifications.*.issue_date' => ['nullable', 'date_format:Y-m-d'],
            'certifications.*.credential_url' => ['nullable', 'url:http,https', 'max:2048'],
            'certifications.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            'certifications.*.description' => ['nullable', 'string', 'max:3000'],
            'generate_qr' => ['sometimes', 'boolean'],
            'qr_error_correction' => ['sometimes', 'in:Q,H'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $currentEmploymentCount = collect($this->input('employment', []))
                ->filter(fn (mixed $employment): bool => is_array($employment) && filter_var($employment['is_current'] ?? false, FILTER_VALIDATE_BOOLEAN))
                ->count();

            $person = $this->route('person');
            $nextStatus = $this->input('status', $person?->status ?? 'active');

            if ($this->boolean('generate_qr') && $nextStatus !== 'active') {
                $validator->errors()->add('generate_qr', 'A public QR code can only be generated for an active profile.');
            }

            if ($currentEmploymentCount > 1) {
                $validator->errors()->add('employment', 'Only one employment record can be marked as current.');
            }
        }];
    }
}
