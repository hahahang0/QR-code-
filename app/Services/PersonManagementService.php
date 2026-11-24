<?php

namespace App\Services;

use App\Models\Person;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PersonManagementService
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, ?User $actor): Person
    {
        $photo = $attributes['profile_photo'] ?? null;
        unset($attributes['profile_photo']);

        $attributes['created_by'] = $actor?->id;
        $attributes['reference_number'] ??= $this->newReferenceNumber();
        $attributes['status'] ??= 'active';

        if ($photo instanceof UploadedFile) {
            $attributes['profile_photo_path'] = $photo->store('people', 'local');
        }

        try {
            return DB::transaction(fn (): Person => Person::query()->create($attributes));
        } catch (Throwable $exception) {
            if (isset($attributes['profile_photo_path'])) {
                Storage::disk('local')->delete($attributes['profile_photo_path']);
            }

            throw $exception;
        }
    }

    /** @param array<string, mixed> $attributes */
    public function update(Person $person, array $attributes): Person
    {
        $photo = $attributes['profile_photo'] ?? null;
        unset($attributes['profile_photo']);
        $previousPhoto = $person->profile_photo_path;

        if ($photo instanceof UploadedFile) {
            $attributes['profile_photo_path'] = $photo->store('people', 'local');
        }

        try {
            DB::transaction(function () use ($person, $attributes): void {
                $person->update($attributes);
            });
        } catch (Throwable $exception) {
            if (isset($attributes['profile_photo_path'])) {
                Storage::disk('local')->delete($attributes['profile_photo_path']);
            }

            throw $exception;
        }

        if ($photo instanceof UploadedFile && $previousPhoto !== null) {
            Storage::disk('local')->delete($previousPhoto);
        }

        return $person->refresh();
    }

    public function archive(Person $person): void
    {
        $person->delete();
    }

    private function newReferenceNumber(): string
    {
        do {
            $referenceNumber = 'QRM-'.random_int(1_000_000, 9_999_999);
        } while (Person::withTrashed()->where('reference_number', $referenceNumber)->exists());

        return $referenceNumber;
    }
}
