<?php

namespace App\Models;

use Database\Factories\PortfolioProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['person_id', 'title', 'image_path', 'description', 'technologies', 'project_url', 'github_url', 'start_date', 'completion_date', 'sort_order'])]
class PortfolioProject extends Model
{
    /** @use HasFactory<PortfolioProjectFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['technologies' => 'array', 'start_date' => 'date', 'completion_date' => 'date'];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
