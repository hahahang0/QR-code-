<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Skill;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class PublicProfileController extends Controller
{
    public function show(string $slug): View
    {
        $person = Person::query()
            ->where('public_slug', $slug)
            ->where('status', 'active')
            ->with(['currentQrCode', 'employmentHistory', 'educations', 'skills', 'portfolioProjects', 'certifications'])
            ->firstOrFail();

        $skillGroups = $person->skills->groupBy(function (Skill $skill): string {
            $skillName = Str::lower($skill->name);

            if (Str::contains($skillName, ['ai', 'machine learning', 'data', 'automation', 'analytics', 'llm'])) {
                return 'AI, Data & Automation';
            }

            if (Str::contains($skillName, ['javascript', 'typescript', 'css', 'tailwind', 'frontend', 'cloud', 'docker', 'aws', 'linux', 'ci/cd'])) {
                return 'Frontend & Cloud DevOps';
            }

            return 'Backend & Systems';
        });

        $onlineLinks = collect();

        if ($person->linkedin_url) {
            $onlineLinks->push([
                'label' => 'LinkedIn',
                'url' => $person->linkedin_url,
                'detail' => trim(parse_url($person->linkedin_url, PHP_URL_PATH) ?: $person->linkedin_url, '/'),
                'icon' => 'linkedin',
            ]);
        }

        if ($person->phone) {
            $onlineLinks->push([
                'label' => 'WhatsApp',
                'url' => 'https://wa.me/'.preg_replace('/\D+/', '', $person->phone),
                'detail' => 'Direct Chat',
                'icon' => 'whatsapp',
            ]);
        }

        foreach ($person->other_social_links ?? [] as $link) {
            if (blank($link['url'] ?? null)) {
                continue;
            }

            $onlineLinks->push([
                'label' => $link['label'] ?? 'Social profile',
                'url' => $link['url'],
                'detail' => parse_url($link['url'], PHP_URL_HOST) ?: trim(parse_url($link['url'], PHP_URL_PATH) ?: $link['url'], '/'),
                'icon' => 'external',
            ]);
        }

        if ($person->github_url) {
            $onlineLinks->push([
                'label' => 'GitHub',
                'url' => $person->github_url,
                'detail' => trim(parse_url($person->github_url, PHP_URL_PATH) ?: $person->github_url, '/'),
                'icon' => 'github',
            ]);
        }

        if ($person->website_url) {
            $onlineLinks->push([
                'label' => 'Website',
                'url' => $person->website_url,
                'detail' => parse_url($person->website_url, PHP_URL_HOST) ?: $person->website_url,
                'icon' => 'website',
            ]);
        }

        return view('profiles.show', compact('person', 'skillGroups', 'onlineLinks'));
    }
}
