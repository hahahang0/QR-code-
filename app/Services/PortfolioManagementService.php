<?php

namespace App\Services;

use App\Models\Person;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class PortfolioManagementService
{
    /** @param array<string, mixed> $sections */
    public function sync(Person $person, array $sections): void
    {
        $newFiles = [];
        $oldFiles = [];

        try {
            DB::transaction(function () use ($person, $sections, &$newFiles, &$oldFiles): void {
                $person->update(['other_social_links' => array_values($sections['other_social_links'] ?? [])]);

                $this->syncRows($person->employmentHistory(), $sections['employment'] ?? [], ['company_logo'], $newFiles, $oldFiles, [
                    'company_name', 'job_title', 'employment_type', 'start_date', 'end_date', 'location', 'description', 'is_current',
                ]);
                $this->syncRows($person->educations(), $sections['education'] ?? [], [], $newFiles, $oldFiles, [
                    'institution_name', 'qualification', 'field_of_study', 'start_year', 'graduation_year', 'description',
                ]);
                $this->syncSkills($person, $sections['skills'] ?? [], $newFiles, $oldFiles);
                $this->syncRows($person->portfolioProjects(), $sections['projects'] ?? [], ['image'], $newFiles, $oldFiles, [
                    'title', 'description', 'technologies', 'project_url', 'github_url', 'start_date', 'completion_date',
                ]);
                $this->syncRows($person->certifications(), $sections['certifications'] ?? [], ['image'], $newFiles, $oldFiles, [
                    'name', 'issuer', 'issue_date', 'credential_url', 'description',
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newFiles);

            throw $exception;
        }

        Storage::disk('public')->delete($oldFiles);
    }

    /** @param list<array<string, mixed>> $rows
     * @param  list<string>  $fileFields
     * @param  list<string>  $fillable
     * @param  list<string>  $newFiles
     * @param  list<string>  $oldFiles
     */
    private function syncRows(HasMany $relation, array $rows, array $fileFields, array &$newFiles, array &$oldFiles, array $fillable): void
    {
        $existing = $relation->get()->keyBy('id');

        foreach (array_values($rows) as $position => $row) {
            $identifier = $row['id'] ?? null;
            $model = $identifier === null ? null : $existing->pull((int) $identifier);

            if ($identifier !== null && $model === null) {
                throw ValidationException::withMessages(['portfolio' => 'A portfolio item does not belong to this person. Refresh the page and try again.']);
            }

            $model ??= $relation->make();
            $attributes = Arr::only($row, $fillable);

            foreach ($fileFields as $fileField) {
                $file = $row[$fileField] ?? null;

                if ($file instanceof UploadedFile) {
                    $path = $file->store('portfolio', 'public');
                    $newFiles[] = $path;
                    $pathField = match ($fileField) {
                        'company_logo' => 'company_logo_path',
                        'image' => 'image_path',
                        default => $fileField.'_path',
                    };

                    if ($model->getAttribute($pathField) !== null) {
                        $oldFiles[] = $model->getAttribute($pathField);
                    }

                    $attributes[$pathField] = $path;
                }
            }

            if (array_key_exists('technologies', $attributes)) {
                $attributes['technologies'] = array_values(array_filter(array_map('trim', explode(',', (string) $attributes['technologies']))));
            }

            $attributes['sort_order'] = $position;
            $model->fill($attributes);
            $model->save();
        }

        foreach ($existing as $model) {
            foreach ($fileFields as $fileField) {
                $pathField = match ($fileField) {
                    'company_logo' => 'company_logo_path',
                    'image' => 'image_path',
                    default => $fileField.'_path',
                };

                if ($model->getAttribute($pathField) !== null) {
                    $oldFiles[] = $model->getAttribute($pathField);
                }
            }

            $model->delete();
        }
    }

    /** @param list<array{name: string}> $rows
     * @param  list<string>  $newFiles
     * @param  list<string>  $oldFiles
     */
    private function syncSkills(Person $person, array $rows, array &$newFiles, array &$oldFiles): void
    {
        $this->syncRows($person->skills(), $rows, [], $newFiles, $oldFiles, ['name']);
    }
}
