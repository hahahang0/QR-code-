import {
    createLocalPeopleStore,
    isValidLocalPersonRecord,
    MAXIMUM_LOCAL_PEOPLE,
    upgradeLocalPersonRecord,
} from './local-people-store.js';

(() => {
    const fieldLabels = {
        full_name: 'Full name',
        date_of_birth: 'Date of birth',
        gender: 'Gender',
        phone: 'Phone number',
        email: 'Email address',
        address: 'Address',
        organization: 'Organization',
        department: 'Department',
        designation: 'Designation',
        reference_number: 'Reference number',
        emergency_contact: 'Emergency contact',
        notes: 'Additional notes',
    };

    const form = document.querySelector('#qr-generator');
    const peoplePanel = document.querySelector('#people-panel');
    const personList = document.querySelector('#local-person-list');
    const searchInput = document.querySelector('#people-search');
    const localStatus = document.querySelector('#local-storage-status');
    const saveLocallyInput = document.querySelector('#save-locally');
    const recordIdInput = document.querySelector('#local_record_id');
    const saveOnlyButton = document.querySelector('#save-person-only');
    const exportButton = document.querySelector('#export-local-people');
    const importInput = document.querySelector('#import-local-people');
    const resultImage = document.querySelector('#qr-image');
    const resultActions = document.querySelector('#qr-result-actions');
    const downloadPng = document.querySelector('#download-png');
    const downloadSvg = document.querySelector('#download-svg');
    const payloadSummary = document.querySelector('#payload-summary');
    const payloadPreview = document.querySelector('#payload-preview');
    const payloadText = document.querySelector('#payload-text');

    if (!form || !personList || !localStatus) {
        return;
    }

    let records = [];
    let storageUnavailable = false;
    let peopleStore;

    try {
        peopleStore = createLocalPeopleStore(window.localStorage);
    } catch (error) {
        storageUnavailable = true;
    }

    function setLocalStatus(message, isError = false) {
        localStatus.textContent = message;
        localStatus.classList.toggle('error', isError);
        localStatus.classList.toggle('local-storage-error', isError);
    }

    function makeId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        return `local-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
    }

    function ensureReferenceNumber() {
        const input = form.elements.namedItem('person[reference_number]');

        if (input.value.trim() !== '') {
            return;
        }

        let referenceNumber;
        do {
            referenceNumber = `QRM-${Math.floor(1_000_000 + Math.random() * 9_000_000)}`;
        } while (records.some((record) => record.person.reference_number === referenceNumber));

        input.value = referenceNumber;
    }

    function readRecords() {
        try {
            storageUnavailable = false;

            return peopleStore.read();
        } catch (error) {
            storageUnavailable = true;
            setLocalStatus(error instanceof DOMException && error.name === 'SecurityError'
                ? 'Browser storage is blocked. You can still generate and download QR codes, but saved people are unavailable.'
                : error.message || 'Browser storage could not be read. Your saved data was left unchanged.', true);

            return [];
        }
    }

    function writeRecords(nextRecords) {
        if (storageUnavailable) {
            throw new Error('Browser storage is unavailable; no saved records were changed.');
        }

        if (nextRecords.length > MAXIMUM_LOCAL_PEOPLE) {
            throw new Error(`This browser can store up to ${MAXIMUM_LOCAL_PEOPLE} people in this workspace.`);
        }

        try {
            peopleStore.write(nextRecords);
            records = nextRecords;
            renderRecords();
        } catch (error) {
            throw error;
        }
    }

    function getPersonFromForm() {
        const person = {};

        form.querySelectorAll('[name^="person["]').forEach((input) => {
            const match = /^person\[([a-z_]+)\]$/u.exec(input.name);

            if (match) {
                person[match[1]] = input.value;
            }
        });

        person.full_name = person.full_name || '';

        return person;
    }

    function getIncludedFields() {
        return [...form.querySelectorAll('input[name="include[]"]:checked')].map((input) => input.value);
    }

    function findCurrentRecord() {
        const id = recordIdInput.value;

        return id ? records.find((record) => record.id === id) : null;
    }

    function savePerson(person, include, errorCorrection, generatedPayload = null) {
        const existing = findCurrentRecord();
        const now = new Date().toISOString();
        const record = {
            id: existing?.id || makeId(),
            person,
            include,
            error_correction: errorCorrection,
            created_at: existing?.created_at || now,
            updated_at: now,
            latest_payload: generatedPayload || existing?.latest_payload || null,
            history: existing?.history || [],
        };

        if (generatedPayload) {
            record.history = [...record.history, {
                created_at: now,
                error_correction: errorCorrection,
                payload: generatedPayload,
            }].slice(-10);
        }

        const nextRecords = existing
            ? records.map((current) => current.id === record.id ? record : current)
            : [record, ...records];

        writeRecords(nextRecords);
        recordIdInput.value = record.id;

        return record;
    }

    function renderRecords() {
        personList.replaceChildren();
        const query = searchInput.value.trim().toLocaleLowerCase();
        const visibleRecords = records.filter((record) => {
            const searchable = [
                record.person.full_name,
                record.person.email,
                record.person.phone,
                record.person.reference_number,
                record.person.organization,
            ].join(' ').toLocaleLowerCase();

            return searchable.includes(query);
        });

        if (visibleRecords.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'helper';
            empty.textContent = records.length === 0
                ? 'No people are saved here yet. Fill out the form and save a person to this browser.'
                : 'No saved person matches this search.';
            personList.append(empty);

            return;
        }

        visibleRecords.forEach((record) => {
            const article = document.createElement('article');
            article.className = 'local-person';
            const avatar = document.createElement('span');
            avatar.className = 'local-person-avatar';
            avatar.textContent = [...record.person.full_name.trim()][0]?.toLocaleUpperCase() || '?';
            const details = document.createElement('div');
            details.className = 'local-person-main';
            const name = document.createElement('strong');
            name.textContent = record.person.full_name;
            const subline = document.createElement('small');
            subline.textContent = [record.person.email, record.person.phone, record.person.reference_number].filter(Boolean).join(' · ') || 'No contact details';
            const qrLine = document.createElement('small');
            qrLine.textContent = record.latest_payload ? `QR generated ${new Date(record.updated_at).toLocaleString()}` : 'No QR generated yet';
            details.append(name, subline, qrLine);

            const actions = document.createElement('div');
            actions.className = 'local-person-actions';
            actions.append(
                createActionButton('Generate QR', 'generate', record.id),
                createActionButton('Edit', 'edit', record.id),
                createActionButton('Delete', 'delete', record.id, true),
            );
            article.append(avatar, details, actions);
            personList.append(article);
        });
    }

    function createActionButton(label, action, id, danger = false) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.dataset.action = action;
        button.dataset.id = id;

        if (danger) {
            button.className = 'secondary';
            button.setAttribute('aria-label', `Delete ${records.find((record) => record.id === id)?.person.full_name || 'person'}`);
        } else {
            button.className = 'secondary';
        }

        return button;
    }

    function populateForm(record) {
        form.reset();
        recordIdInput.value = record.id;
        saveLocallyInput.checked = true;

        Object.entries(record.person).forEach(([field, value]) => {
            const input = form.elements.namedItem(`person[${field}]`);

            if (input) {
                input.value = value;
            }
        });

        form.querySelectorAll('input[name="include[]"]').forEach((input) => {
            input.checked = record.include.includes(input.value);
        });
        form.elements.namedItem('error_correction').value = record.error_correction;
        document.querySelector('#generate-tab').click();
        form.querySelector('[name="person[full_name]"]').focus();
    }

    function displayQrResult(result) {
        document.querySelector('#qr-placeholder-heading')?.setAttribute('hidden', 'hidden');
        const heading = document.querySelector('#qr-result h2');
        heading.textContent = `QR code ready: ${result.person.full_name}`;
        resultImage.src = result.svg;
        resultImage.alt = `QR code for ${result.person.full_name}`;
        resultImage.hidden = false;
        resultActions.hidden = false;
        downloadPng.href = result.png;
        downloadSvg.href = result.svg;
        payloadSummary.textContent = `Version 1 · ${result.bytes} UTF-8 bytes · error correction ${result.errorCorrection}`;
        payloadSummary.hidden = false;
        payloadPreview.hidden = false;
        payloadText.textContent = result.payload;
    }

    function showValidationErrors(responseData) {
        const messages = Object.values(responseData.errors || {}).flat();
        throw new Error(messages.join(' ') || responseData.message || 'QR code generation failed.');
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!form.reportValidity()) {
            return;
        }

        ensureReferenceNumber();

        const submitButton = event.submitter;
        const previousLabel = submitButton?.textContent;
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Generating…';
        }

        const person = getPersonFromForm();
        const include = getIncludedFields();
        const errorCorrection = form.elements.namedItem('error_correction').value;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();

            if (!response.ok) {
                showValidationErrors(result);
            }

            displayQrResult(result);

            if (saveLocallyInput.checked) {
                savePerson(person, include, errorCorrection, result.payload);
                setLocalStatus(`${person.full_name} was saved in this browser and a QR code was generated.`);
            } else {
                setLocalStatus(`QR code generated for ${person.full_name}. This record was not saved.`);
                recordIdInput.value = '';
            }
        } catch (error) {
            setLocalStatus(error.message || 'QR code generation failed. No saved record was changed.', true);
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = previousLabel || 'Generate QR code';
            }
        }
    });

    saveOnlyButton.addEventListener('click', () => {
        if (!form.reportValidity()) {
            return;
        }

        try {
            ensureReferenceNumber();
            const person = getPersonFromForm();
            const record = savePerson(person, getIncludedFields(), form.elements.namedItem('error_correction').value);
            setLocalStatus(`${record.person.full_name} was saved in this browser. No QR was generated.`);
            document.querySelector('#people-tab').click();
        } catch (error) {
            setLocalStatus(error.message, true);
        }
    });

    personList.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');

        if (!button) {
            return;
        }

        const record = records.find((item) => item.id === button.dataset.id);

        if (!record) {
            setLocalStatus('That saved person could not be found.', true);
            return;
        }

        if (button.dataset.action === 'edit') {
            populateForm(record);
            return;
        }

        if (button.dataset.action === 'generate') {
            populateForm(record);
            form.requestSubmit();
            return;
        }

        if (button.dataset.action === 'delete' && window.confirm(`Delete ${record.person.full_name} from this browser? This cannot be undone.`)) {
            try {
                writeRecords(records.filter((item) => item.id !== record.id));
                setLocalStatus(`${record.person.full_name} was removed from this browser.`);
            } catch (error) {
                setLocalStatus(error.message, true);
            }
        }
    });

    searchInput.addEventListener('input', renderRecords);

    exportButton.addEventListener('click', () => {
        const exported = {
            schema_version: 1,
            type: 'qr-manager-local-people',
            exported_at: new Date().toISOString(),
            records,
        };
        const blob = new Blob([JSON.stringify(exported, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = 'qr-manager-people.json';
        anchor.hidden = true;
        document.body.append(anchor);
        anchor.click();
        anchor.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
        setLocalStatus(`${records.length} local record${records.length === 1 ? '' : 's'} exported.`);
    });

    importInput.addEventListener('change', async () => {
        const file = importInput.files?.[0];

        if (!file) {
            return;
        }

        try {
            if (file.size > 5 * 1024 * 1024) {
                throw new Error('Choose a JSON file no larger than 5 MB.');
            }

            const imported = JSON.parse(await file.text());

            if (imported.schema_version !== 1 || imported.type !== 'qr-manager-local-people' || !Array.isArray(imported.records)) {
                throw new Error('This file is not a supported QR Manager people export.');
            }

            const normalizedRecords = imported.records.map(upgradeLocalPersonRecord);

            if (normalizedRecords.some((record) => !record || !isValidLocalPersonRecord(record))
                || new Set(normalizedRecords.map((record) => record.id)).size !== normalizedRecords.length) {
                throw new Error('The import contains a malformed person record. No records were imported.');
            }

            const merged = new Map(records.map((record) => [record.id, record]));
            normalizedRecords.forEach((record) => merged.set(record.id, record));
            writeRecords([...merged.values()]);
            setLocalStatus(`${imported.records.length} record${imported.records.length === 1 ? '' : 's'} imported into this browser.`);
        } catch (error) {
            setLocalStatus(error.message || 'The JSON file could not be imported.', true);
        } finally {
            importInput.value = '';
        }
    });

    records = readRecords();
    renderRecords();
    if (!storageUnavailable) {
        setLocalStatus('People saved in this browser are not uploaded to a server or database.');
    }
})();
