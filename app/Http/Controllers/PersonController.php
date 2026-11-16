<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Person;
use App\Services\ActivityLogService;
use App\Services\ApplicationSettingsService;
use App\Services\PersonManagementService;
use App\Services\QrPayloadService;
use App\Services\QrRecordService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Person::class);
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
        $this->authorize('create', Person::class);

        return view('admin.people.create', ['defaultErrorCorrection' => $settings->defaultErrorCorrection()]);
    }

    public function store(StorePersonRequest $request, PersonManagementService $people, QrRecordService $qrRecords, QrPayloadService $payloads, ApplicationSettingsService $settings, ActivityLogService $activityLogs): RedirectResponse
    {
        $validated = $request->validated();
        $generateQr = (bool) ($validated['generate_qr'] ?? false);
        $includedFields = $validated['qr_include'] ?? [];
        $errorCorrection = $validated['qr_error_correction'] ?? $settings->defaultErrorCorrection();
        unset($validated['generate_qr'], $validated['qr_include'], $validated['qr_error_correction']);

        if ($generateQr) {
            try {
                $payload = $payloads->create($validated, $includedFields);
                $payloads->assertWithinCapacity($payload, $errorCorrection);
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['full_name' => $exception->getMessage()]);
            }
        }

        $person = $people->create($validated, $request->user());

        if ($generateQr) {
            $qrRecords->generate($person, $request->user(), $includedFields, $errorCorrection);
        }

        $activityLogs->record('person.created', $request, $person);

        return redirect()->route('admin.people.show', $person)->with('status', 'Person added successfully.');
    }

    public function show(Person $person, QrRecordService $qrRecords, ApplicationSettingsService $settings): View
    {
        $this->authorize('view', $person);
        $person->load(['currentQrCode', 'qrCodes.generator']);
        $currentQrImage = $person->currentQrCode ? $qrRecords->render($person->currentQrCode) : null;

        $defaultErrorCorrection = $settings->defaultErrorCorrection();

        return view('admin.people.show', compact('person', 'currentQrImage', 'defaultErrorCorrection'));
    }

    public function photo(Person $person): BinaryFileResponse
    {
        $this->authorize('view', $person);
        abort_if($person->profile_photo_path === null, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($person->profile_photo_path), 404);

        $response = response()->file($disk->path($person->profile_photo_path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function edit(Person $person): View
    {
        $this->authorize('update', $person);

        $person->load('currentQrCode');

        return view('admin.people.edit', compact('person'));
    }

    public function update(UpdatePersonRequest $request, Person $person, PersonManagementService $people, QrRecordService $qrRecords, QrPayloadService $payloads, ActivityLogService $activityLogs): RedirectResponse
    {
        $validated = $request->validated();
        $currentQr = $person->qrCodes()->where('is_current', true)->first();
        $regenerateQr = false;
        $includedFields = array_values(array_filter($validated['qr_include'] ?? ($validated['qr_include_present'] ?? false ? [] : ($currentQr?->included_fields ?? []))));
        $errorCorrection = $validated['qr_error_correction'] ?? $currentQr?->error_correction ?? 'Q';
        $generateQr = (bool) ($validated['generate_qr'] ?? false);
        unset($validated['qr_include'], $validated['qr_error_correction'], $validated['generate_qr'], $validated['qr_include_present']);

        if ($currentQr !== null || $generateQr) {
            try {
                $candidatePayload = $payloads->create(array_merge($person->getAttributes(), $validated), $includedFields);
                $payloads->assertWithinCapacity($candidatePayload, $errorCorrection);
                $regenerateQr = $currentQr === null || $generateQr || $candidatePayload !== $currentQr->payload || $includedFields !== ($currentQr->included_fields ?? []) || $errorCorrection !== $currentQr->error_correction;
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['full_name' => $exception->getMessage()]);
            }
        }

        $person = $people->update($person, $validated);

        if ($currentQr !== null && $regenerateQr) {
            $qrRecords->generate($person, $request->user(), $includedFields, $errorCorrection);
        } elseif ($currentQr === null && $generateQr && $regenerateQr) {
            $qrRecords->generate($person, $request->user(), $includedFields, $errorCorrection);
        }

        $activityLogs->record('person.updated', $request, $person);

        return redirect()->route('admin.people.show', $person)->with('status', 'Person updated successfully.');
    }

    public function destroy(Request $request, Person $person, ActivityLogService $activityLogs): RedirectResponse
    {
        $this->authorize('delete', $person);
        $activityLogs->record('person.archived', $request, $person);

        if ($person->profile_photo_path !== null) {
            Storage::disk('local')->delete($person->profile_photo_path);
        }

        $person->delete();

        return redirect()->route('admin.people.index')->with('status', 'Person archived successfully.');
    }
}
