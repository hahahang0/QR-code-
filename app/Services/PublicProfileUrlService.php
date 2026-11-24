<?php

namespace App\Services;

use App\Models\Person;

class PublicProfileUrlService
{
    public function for(Person $person): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $path = route('profiles.show', ['slug' => $person->public_slug], false);

        return $baseUrl.$path;
    }
}
