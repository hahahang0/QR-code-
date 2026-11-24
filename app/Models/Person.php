<?php

namespace App\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['full_name', 'date_of_birth', 'gender', 'phone', 'email', 'address', 'organization', 'department', 'designation', 'reference_number', 'emergency_contact', 'notes', 'profile_photo_path', 'status', 'created_by', 'public_slug', 'professional_bio', 'location', 'website_url', 'linkedin_url', 'github_url', 'other_social_links'])]
class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'other_social_links' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Person $person): void {
            if (filled($person->public_slug)) {
                return;
            }

            $base = Str::slug($person->full_name) ?: 'person';

            do {
                $slug = $base.'-'.Str::lower(Str::random(8));
            } while (static::withTrashed()->where('public_slug', $slug)->exists());

            $person->public_slug = $slug;
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    public function currentQrCode(): HasOne
    {
        return $this->hasOne(QrCode::class)->where('is_current', true)->latestOfMany();
    }

    public function employmentHistory(): HasMany
    {
        return $this->hasMany(EmploymentHistory::class)->orderByDesc('is_current')->orderByDesc('start_date');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class)->orderByDesc('graduation_year');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class)->orderBy('sort_order')->orderBy('name');
    }

    public function portfolioProjects(): HasMany
    {
        return $this->hasMany(PortfolioProject::class)->orderBy('sort_order')->orderByDesc('completion_date');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class)->orderBy('sort_order')->orderByDesc('issue_date');
    }
}
