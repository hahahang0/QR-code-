<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManagePersonRequest;
use App\Models\Person;
use App\Services\ActivityLogService;
use App\Services\ApplicationSettingsService;
use App\Services\PersonManagementService;
use App\Services\PortfolioManagementService;
use App\Services\PublicProfileUrlService;
use App\Services\QrRecordService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:active,archived'],
            'qr_status' => ['nullable', 'string', 'in:missing,generated'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Person::query()->with('currentQrCode')
            ->when(isset($filters['search']) && trim($filters['search']) !== '', function ($query) use ($filters): void {
                $search = trim($filters['search']);
                $query->where(function ($query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(($filters['qr_status'] ?? null) === 'missing', fn ($query) => $query->whereDoesntHave('qrCodes', fn ($qr) => $qr->where('is_current', true)))
            ->when(($filters['qr_status'] ?? null) === 'generated', fn ($query) => $query->whereHas('qrCodes', fn ($qr) => $qr->where('is_current', true)))
            ->latest('people.id');
        $people = $query
            ->paginate(15)
            ->withQueryString();
        $counts = [
            'all' => Person::query()->count(),
            'active' => Person::query()->where('status', 'active')->count(),
            'archived' => Person::query()->where('status', 'archived')->count(),
            'without_qr' => Person::query()->whereDoesntHave('qrCodes', fn ($qr) => $qr->where('is_current', true))->count(),
        ];

        return view('admin.people.index', compact('people', 'counts'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:active,archived'],
            'qr_status' => ['nullable', 'string', 'in:missing,generated'],
        ]);
        $people = Person::query()->with('currentQrCode')
            ->when(isset($filters['search']) && trim($filters['search']) !== '', function ($query) use ($filters): void {
                $search = trim($filters['search']);
                $query->where(fn ($query) => $query->where('full_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            })
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(($filters['qr_status'] ?? null) === 'missing', fn ($query) => $query->whereDoesntHave('qrCodes', fn ($qr) => $qr->where('is_current', true)))
            ->when(($filters['qr_status'] ?? null) === 'generated', fn ($query) => $query->whereHas('qrCodes', fn ($qr) => $qr->where('is_current', true)))
            ->orderBy('full_name')->get();

        return response()->streamDownload(function () use ($people): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Name', 'Reference', 'Email', 'Phone', 'Address', 'Organization', 'Department', 'Designation', 'Status', 'QR generated', 'Registered']);
            foreach ($people as $person) {
                fputcsv($stream, [$person->full_name, $person->reference_number, $person->email, $person->phone, $person->address, $person->organization, $person->department, $person->designation, $person->status, $person->currentQrCode ? 'Yes' : 'No', $person->created_at->toDateString()]);
            }
            fclose($stream);
        }, 'people.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(ApplicationSettingsService $settings): View
    {
        return view('admin.people.create', ['defaultErrorCorrection' => $settings->defaultErrorCorrection()]);
    }

    public function store(ManagePersonRequest $request, PersonManagementService $people, PortfolioManagementService $portfolio, QrRecordService $qrRecords, ApplicationSettingsService $settings, ActivityLogService $activityLogs): RedirectResponse
    {
        $validated = $request->validated();
        $generateQr = (bool) ($validated['generate_qr'] ?? false);
        $errorCorrection = $validated['qr_error_correction'] ?? $settings->defaultErrorCorrection();
        $portfolioSections = $this->portfolioSections($validated);
        unset(
            $validated['generate_qr'],
            $validated['qr_error_correction'],
            $validated['other_social_links'],
            $validated['employment'],
            $validated['education'],
            $validated['skills'],
            $validated['projects'],
            $validated['certifications'],
        );

        $person = $people->create($validated, $request->user());
        $portfolio->sync($person, $portfolioSections);

        if ($generateQr && $person->status === 'active') {
            $qrRecords->generate($person, $request->user(), $errorCorrection);
        }

        $activityLogs->record('person.created', $request, $person);

        return redirect()->route('admin.people.show', $person)->with('status', 'Person added successfully.');
    }

    public function show(Person $person, ApplicationSettingsService $settings, PublicProfileUrlService $profileUrls): View
    {
        $person->load(['currentQrCode', 'qrCodes.generator', 'employmentHistory', 'educations', 'skills', 'portfolioProjects', 'certifications']);
        $profileUrl = $profileUrls->for($person);

        $defaultErrorCorrection = $settings->defaultErrorCorrection();

        return view('admin.people.show', compact('person', 'defaultErrorCorrection', 'profileUrl'));
    }

    public function photo(string $slug): BinaryFileResponse
    {
        $person = Person::query()->where('public_slug', $slug)->where('status', 'active')->firstOrFail();
        abort_if($person->profile_photo_path === null, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($person->profile_photo_path), 404);

        $response = response()->file($disk->path($person->profile_photo_path), [
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setPublic();

        return $response;
    }

    public function edit(Person $person): View
    {
        $person->load(['currentQrCode', 'employmentHistory', 'educations', 'skills', 'portfolioProjects', 'certifications']);

        return view('admin.people.edit', compact('person'));
    }

    public function update(ManagePersonRequest $request, Person $person, PersonManagementService $people, PortfolioManagementService $portfolio, QrRecordService $qrRecords, ActivityLogService $activityLogs): RedirectResponse
    {
        $validated = $request->validated();
        $currentQr = $person->qrCodes()->where('is_current', true)->first();
        $errorCorrection = $validated['qr_error_correction'] ?? $currentQr?->error_correction ?? 'Q';
        $generateQr = (bool) ($validated['generate_qr'] ?? false);
        $portfolioSections = $this->portfolioSections($validated);
        unset(
            $validated['generate_qr'],
            $validated['qr_error_correction'],
            $validated['other_social_links'],
            $validated['employment'],
            $validated['education'],
            $validated['skills'],
            $validated['projects'],
            $validated['certifications'],
        );

        $person = $people->update($person, $validated);
        $portfolio->sync($person, $portfolioSections);

        if ($person->status !== 'active') {
            $person->qrCodes()->where('is_current', true)->update(['is_current' => false]);
        } elseif ($generateQr) {
            $qrRecords->generate($person, $request->user(), $errorCorrection);
        }

        $activityLogs->record('person.updated', $request, $person);

        return redirect()->route('admin.people.show', $person)->with('status', 'Person updated successfully.');
    }

    public function destroy(Request $request, Person $person, ActivityLogService $activityLogs): RedirectResponse
    {
        $activityLogs->record('person.archived', $request, $person);
        $person->qrCodes()->where('is_current', true)->update(['is_current' => false]);
        $person->delete();

        return redirect()->route('admin.people.index')->with('status', 'Person archived successfully.');
    }

    /** @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function portfolioSections(array $validated): array
    {
        return [
            'other_social_links' => $validated['other_social_links'] ?? [],
            'employment' => $validated['employment'] ?? [],
            'education' => $validated['education'] ?? [],
            'skills' => $validated['skills'] ?? [],
            'projects' => $validated['projects'] ?? [],
            'certifications' => $validated['certifications'] ?? [],
        ];
    }
}
