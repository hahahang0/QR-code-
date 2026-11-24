<?php

namespace App\Models;

use Database\Factories\EmploymentHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['person_id', 'company_name', 'company_logo_path', 'job_title', 'employment_type', 'start_date', 'end_date', 'location', 'description', 'is_current'])]
class EmploymentHistory extends Model
{
    /** @use HasFactory<EmploymentHistoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_current' => 'boolean'];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
