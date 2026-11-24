<?php

use App\Models\Person;
use App\Models\QrCode as QrCodeRecord;
use App\Services\PublicProfileUrlService;
use App\Services\QrCodeService;
use App\Services\QrRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Zxing\QrReader;

uses(RefreshDatabase::class);

it('creates a database-backed portfolio with multiple sections and a URL QR code without authentication', function () {
    $response = $this->post(route('admin.people.store'), [
        'full_name' => 'Zoë 李 Sharma',
        'designation' => 'Robotics Engineer',
        'professional_bio' => 'Building accessible robotics tools.',
        'location' => 'Kathmandu, Nepal',
        'email' => 'zoe@example.test',
        'phone' => '+977 980 123 4567',
        'website_url' => 'https://zoe.example.test',
        'employment' => [
            ['company_name' => 'Acme Robotics', 'job_title' => 'Engineer', 'employment_type' => 'Full-time', 'start_date' => '2024-01-01', 'is_current' => '1'],
            ['company_name' => 'Old Robotics', 'job_title' => 'Intern', 'start_date' => '2022-01-01', 'end_date' => '2023-01-01'],
        ],
        'education' => [['institution_name' => 'Tribhuvan University', 'qualification' => 'BSc', 'field_of_study' => 'Engineering', 'start_year' => '2019', 'graduation_year' => '2023']],
        'skills' => [['name' => 'Laravel'], ['name' => 'Robotics']],
        'projects' => [['title' => 'Assistive robot', 'description' => 'A helpful project.', 'technologies' => 'PHP, C++', 'project_url' => 'https://projects.example.test/robot']],
        'certifications' => [['name' => 'Safety design', 'issuer' => 'Example Board', 'issue_date' => '2024-04-01']],
        'other_social_links' => [['label' => 'Research', 'url' => 'https://research.example.test/zoe']],
        'generate_qr' => '1',
        'qr_error_correction' => 'Q',
    ]);

    $person = Person::query()->firstOrFail();
    $profileUrl = app(PublicProfileUrlService::class)->for($person);

    $response->assertRedirect(route('admin.people.show', $person))->assertSessionHasNoErrors();
    expect($person->public_slug)->toStartWith('zoe-sharma-')
        ->and($person->employmentHistory()->count())->toBe(2)
        ->and($person->educations()->count())->toBe(1)
        ->and($person->skills()->count())->toBe(2)
        ->and($person->portfolioProjects()->firstOrFail()->technologies)->toBe(['PHP', 'C++'])
        ->and($person->certifications()->count())->toBe(1)
        ->and($person->currentQrCode()->firstOrFail()->schema_version)->toBe(2)
        ->and($person->currentQrCode()->firstOrFail()->payload)->toBe($profileUrl)
        ->and($profileUrl)->toContain('/profile/'.$person->public_slug);

    $response = $this->get(route('profiles.show', ['slug' => $person->public_slug]));
    $response->assertOk()
        ->assertSee('Zoë 李 Sharma')
        ->assertSee('Robotics Engineer')
        ->assertSee('Acme Robotics')
        ->assertSee('Tribhuvan University')
        ->assertSee('Assistive robot')
        ->assertSee('Safety design')
        ->assertSee('Laravel')
        ->assertDontSee('Kathmandu private address')
        ->assertDontSee('Emergency contact');
});

it('independently decodes a generated QR image to the configured public profile URL', function () {
    $person = Person::factory()->create(['full_name' => 'Grace Hopper']);
    $qrCode = app(QrRecordService::class)->generate($person, null, 'Q');
    $image = app(QrCodeService::class)->generate($qrCode->payload, $qrCode->error_correction, 512);
    $pngBytes = base64_decode(substr($image['png'], strlen('data:image/png;base64,')), true);
    $reader = new QrReader($pngBytes, QrReader::SOURCE_TYPE_BLOB);

    expect($reader->text())->toBe(app(PublicProfileUrlService::class)->for($person))
        ->and(json_decode((string) $reader->text(), true))->toBeNull()
        ->and(QrCodeRecord::query()->count())->toBe(1);
});

it('keeps the profile URL stable when the person name changes and hides archived profiles', function () {
    $person = Person::factory()->create(['full_name' => 'Original Name']);
    $stableSlug = $person->public_slug;
    $this->put(route('admin.people.update', $person), ['full_name' => 'Updated Name'])->assertRedirect(route('admin.people.show', $person));

    expect($person->fresh()->public_slug)->toBe($stableSlug);
    $this->get(route('profiles.show', ['slug' => $stableSlug]))->assertOk()->assertSee('Updated Name');

    $this->delete(route('admin.people.destroy', $person))->assertRedirect(route('admin.people.index'));
    $this->get(route('profiles.show', ['slug' => $stableSlug]))->assertNotFound();
    $this->get('/profile/not-a-real-profile')->assertNotFound();
});

it('serves a validated profile photo only for an active portfolio', function () {
    Storage::fake('local');
    Storage::fake('public');

    $this->post(route('admin.people.store'), [
        'full_name' => 'Photo Person',
        'profile_photo' => UploadedFile::fake()->image('portrait.jpg'),
    ])->assertRedirect();

    $person = Person::query()->firstOrFail();
    Storage::disk('local')->assertExists($person->profile_photo_path);
    $this->get(route('profiles.photo', ['slug' => $person->public_slug]))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $person->update(['status' => 'archived']);
    $this->get(route('profiles.photo', ['slug' => $person->public_slug]))->assertNotFound();
});

it('escapes untrusted portfolio text and never renders private record fields publicly', function () {
    $person = Person::factory()->create([
        'full_name' => '<script>alert(1)</script>',
        'notes' => 'internal-only-notes',
        'address' => 'residential-only-address',
        'emergency_contact' => 'private-emergency-contact',
        'date_of_birth' => '1990-01-02',
        'gender' => 'private-gender',
    ]);

    $this->get(route('profiles.show', ['slug' => $person->public_slug]))
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('internal-only-notes')
        ->assertDontSee('residential-only-address')
        ->assertDontSee('private-emergency-contact')
        ->assertDontSee('private-gender');
});
