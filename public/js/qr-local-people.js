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
    const activityStorageKey = 'qr-manager.activity.v1';
    const qrList = document.querySelector('#local-qr-list');
    const qrPanel = document.querySelector('#qr-panel');
    const activityList = document.querySelector('#local-activity-list');
    const qrSearchInput = document.querySelector('#qr-search');
    const qrFilterInput = document.querySelector('#qr-status-filter');
    const peopleStatusFilter = document.querySelector('#people-status-filter');
    const peopleEmpty = document.querySelector('#people-empty');
    const qrEmpty = document.querySelector('#qr-empty');
    const activityEmpty = document.querySelector('#activity-empty');
    const peoplePageSize = 10;

    if (!form || !personList || !localStatus) {
        return;
    }

    let records = [];
    let storageUnavailable = false;
    let activePeopleFilter = 'all';
    let currentPeoplePage = 1;
    let currentQrPage = 1;
    let qrPreviewCache = new Map();
    let peopleStore;

    try {
        peopleStore = createLocalPeopleStore(window.localStorage);
    } catch (error) {
        storageUnavailable = true;
    }

    function setLocalStatus(message, isError = false) {
        [localStatus, document.querySelector('#generator-status')].forEach((status) => {
            status.textContent = message;
            status.classList.toggle('error', isError);
            status.classList.toggle('local-storage-error', isError);
        });
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
        let existingPayloadStillMatches = false;

        if (existing?.latest_payload) {
            try {
                const previousQrPerson = JSON.parse(existing.latest_payload).person;
                existingPayloadStillMatches = Object.entries(previousQrPerson).every(([field, value]) => person[field] === value);
            } catch {
                existingPayloadStillMatches = false;
            }
        }

        const record = {
            id: existing?.id || makeId(),
            person,
            include,
            error_correction: errorCorrection,
            created_at: existing?.created_at || now,
            updated_at: now,
            latest_payload: generatedPayload || (existingPayloadStillMatches ? existing.latest_payload : null),
            history: existing?.history || [],
            status: existing?.status || 'active',
        };

        if (generatedPayload) {
            record.history = [...record.history, {
                created_at: now,
                error_correction: errorCorrection,
                payload: generatedPayload,
            }].slice(-10);
        }

        if (generatedPayload) {
            qrPreviewCache.delete(record.id);
        }

        const nextRecords = existing
            ? records.map((current) => current.id === record.id ? record : current)
            : [record, ...records];

        writeRecords(nextRecords);
        recordIdInput.value = record.id;
        logActivity(generatedPayload ? 'QR code generated' : existing ? 'Person updated' : 'Person added', person.full_name);
        renderRecords();

        return record;
    }

    function createCell(text, className = '') {
        const cell = document.createElement('td');
        cell.textContent = text || '—';

        if (className) {
            cell.className = className;
        }

        return cell;
    }

    function getActivity() {
        try {
            const activity = JSON.parse(window.localStorage.getItem(activityStorageKey) || '[]');

            return Array.isArray(activity) ? activity.filter((item) => item && typeof item.action === 'string' && typeof item.person === 'string' && typeof item.created_at === 'string').slice(0, 300) : [];
        } catch {
            return [];
        }
    }

    function logActivity(action, person) {
        try {
            const activity = getActivity();
            activity.unshift({ id: makeId(), action, person, created_at: new Date().toISOString() });
            window.localStorage.setItem(activityStorageKey, JSON.stringify(activity.slice(0, 300)));
        } catch {
            // Activity logging must not prevent a person or QR record from being saved.
        }
    }

    function makePersonLink(record) {
        const link = document.createElement('span');
        link.className = 'person-link';
        const avatar = document.createElement('span');
        avatar.className = 'table-avatar';
        avatar.textContent = [...record.person.full_name.trim()][0]?.toLocaleUpperCase() || '?';
        const details = document.createElement('span');
        const name = document.createElement('span');
        name.textContent = record.person.full_name;
        const email = document.createElement('small');
        email.textContent = record.person.email || record.person.reference_number || 'No email added';
        details.append(name, email);
        link.append(avatar, details);

        return link;
    }

    function createActionButton(label, action, id, danger = false) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.dataset.action = action;
        button.dataset.id = id;
        button.className = danger ? 'link-danger' : 'text-link';
        button.setAttribute('aria-label', `${label} ${records.find((record) => record.id === id)?.person.full_name || 'person'}`);

        return button;
    }

    function renderPeople() {
        personList.replaceChildren();
        const query = `${searchInput.value} ${document.querySelector('#global-search').value}`.trim().toLocaleLowerCase();
        const status = peopleStatusFilter.value;
        const counts = {
            all: records.length,
            active: records.filter((record) => record.status !== 'inactive').length,
            inactive: records.filter((record) => record.status === 'inactive').length,
            missing: records.filter((record) => !record.latest_payload).length,
        };
        document.querySelector('#people-count-all').textContent = counts.all;
        document.querySelector('#people-count-active').textContent = counts.active;
        document.querySelector('#people-count-inactive').textContent = counts.inactive;
        document.querySelector('#people-count-missing').textContent = counts.missing;
        document.querySelector('#sidebar-people-count').textContent = records.length;

        const visibleRecords = records.filter((record) => {
            const searchable = [
                record.person.full_name,
                record.person.email,
                record.person.phone,
                record.person.reference_number,
                record.person.organization,
            ].join(' ').toLocaleLowerCase();
            const matchesQuickFilter = activePeopleFilter === 'all'
                || (activePeopleFilter === 'active' && record.status !== 'inactive')
                || (activePeopleFilter === 'inactive' && record.status === 'inactive')
                || (activePeopleFilter === 'not-generated' && !record.latest_payload);
            const matchesStatus = status === 'all' || record.status === status;

            return searchable.includes(query) && matchesQuickFilter && matchesStatus;
        });
        const pageCount = Math.max(1, Math.ceil(visibleRecords.length / peoplePageSize));
        currentPeoplePage = Math.min(currentPeoplePage, pageCount);
        const pageRecords = visibleRecords.slice((currentPeoplePage - 1) * peoplePageSize, currentPeoplePage * peoplePageSize);

        pageRecords.forEach((record) => {
            const row = document.createElement('tr');
            const checkboxCell = document.createElement('td');
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.setAttribute('aria-label', `Select ${record.person.full_name}`);
            checkboxCell.append(checkbox);
            const personCell = document.createElement('td');
            personCell.append(makePersonLink(record));
            const statusCell = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = `badge ${record.status === 'inactive' ? 'badge-gray' : 'badge-green'}`;
            badge.textContent = record.status === 'inactive' ? 'Inactive' : record.latest_payload ? 'Active' : 'Not Generated';
            statusCell.append(badge);
            const actions = document.createElement('div');
            actions.className = 'row-actions';
            actions.append(
                createActionButton('View', 'view', record.id),
                createActionButton('Edit', 'edit', record.id),
                createActionButton('QR', 'generate', record.id),
                createActionButton(record.status === 'inactive' ? 'Restore' : 'Archive', 'archive', record.id, record.status !== 'inactive'),
                createActionButton('Delete', 'delete', record.id, true),
            );
            const actionCell = document.createElement('td');
            actionCell.append(actions);
            row.append(checkboxCell, personCell, createCell(record.person.phone), createCell(record.person.organization), statusCell, createCell(new Date(record.created_at).toLocaleDateString()), actionCell);
            personList.append(row);
        });

        peopleEmpty.hidden = visibleRecords.length > 0;
        document.querySelector('#people-count-summary').textContent = `${visibleRecords.length ? (currentPeoplePage - 1) * peoplePageSize + 1 : 0}–${Math.min(currentPeoplePage * peoplePageSize, visibleRecords.length)} of ${visibleRecords.length} people`;
        document.querySelector('#people-page-label').textContent = `Page ${currentPeoplePage} of ${pageCount}`;
    }

    function renderDashboard() {
        const generated = records.filter((record) => Boolean(record.latest_payload));
        const active = generated.filter((record) => record.status !== 'inactive');
        const notGenerated = records.filter((record) => !record.latest_payload);
        document.querySelector('#stat-total-people').textContent = records.length.toLocaleString();
        document.querySelector('#stat-qr-generated').textContent = generated.length.toLocaleString();
        document.querySelector('#stat-qr-active').textContent = active.length.toLocaleString();
        document.querySelector('#stat-inactive').textContent = records.filter((record) => record.status === 'inactive').length.toLocaleString();
        document.querySelector('#qr-ring-total').textContent = generated.length.toLocaleString();
        document.querySelector('#qr-ring-generated').textContent = generated.length.toLocaleString();
        document.querySelector('#qr-ring-missing').textContent = notGenerated.length.toLocaleString();
        const percentage = records.length ? Math.round((generated.length / records.length) * 100) : 0;
        document.querySelector('#qr-status-ring').style.background = `conic-gradient(#8157d8 0deg ${percentage * 3.6}deg, #eeeaf6 ${percentage * 3.6}deg 360deg)`;

        const recentPeople = document.querySelector('#dashboard-recent-people');
        recentPeople.replaceChildren();
        records.slice().sort((a, b) => b.created_at.localeCompare(a.created_at)).slice(0, 5).forEach((record) => {
            const row = document.createElement('tr');
            const person = document.createElement('td');
            person.append(makePersonLink(record));
            const badge = document.createElement('span');
            badge.className = `badge ${record.latest_payload ? 'badge-green' : 'badge-gray'}`;
            badge.textContent = record.latest_payload ? 'Active' : 'Not Generated';
            const qrCell = document.createElement('td');
            qrCell.append(badge);
            row.append(person, createCell(record.person.organization), qrCell);
            row.append(createCell(new Date(record.created_at).toLocaleDateString()));
            recentPeople.append(row);
        });

        const chart = document.querySelector('#registration-chart');
        chart.replaceChildren();
        const range = Number(document.querySelector('#dashboard-range').value || 7);
        const days = Array.from({ length: range }, (_, index) => {
            const day = new Date();
            day.setDate(day.getDate() - (range - index - 1));
            const key = day.toISOString().slice(0, 10);
            return { key, label: day.toLocaleDateString(undefined, { weekday: range > 7 ? undefined : 'short', month: 'numeric', day: 'numeric' }), total: records.filter((record) => record.created_at.slice(0, 10) === key).length };
        });
        const maximum = Math.max(1, ...days.map((day) => day.total));
        days.forEach((day) => {
            const column = document.createElement('div');
            column.className = 'chart-column';
            const value = document.createElement('span');
            value.className = 'chart-value';
            value.textContent = day.total || '';
            const track = document.createElement('div');
            track.className = 'chart-track';
            const fill = document.createElement('i');
            fill.style.height = `${Math.max(3, Math.round(day.total / maximum * 100))}%`;
            track.append(fill);
            const label = document.createElement('small');
            label.textContent = day.label;
            column.append(value, track, label);
            chart.append(column);
        });
    }

    function renderQrCodes() {
        qrList.replaceChildren();
        const query = qrSearchInput.value.trim().toLocaleLowerCase();
        const status = qrFilterInput.value;
        const visibleRecords = records.filter((record) => {
            const searchable = [record.person.full_name, record.person.reference_number, record.person.organization].join(' ').toLocaleLowerCase();
            return searchable.includes(query) && (status === 'all' || (status === 'generated' ? Boolean(record.latest_payload) : !record.latest_payload));
        });
        const pageCount = Math.max(1, Math.ceil(visibleRecords.length / 10));
        currentQrPage = Math.min(currentQrPage, pageCount);
        const start = (currentQrPage - 1) * 10;
        const pageRecords = visibleRecords.slice(start, start + 10);
        document.querySelector('#qr-count-all').textContent = records.length;
        document.querySelector('#qr-count-active').textContent = records.filter((record) => record.latest_payload && record.status !== 'inactive').length;
        document.querySelector('#qr-count-missing').textContent = records.filter((record) => !record.latest_payload).length;

        pageRecords.forEach((record) => {
            const row = document.createElement('tr');
            const thumbnailCell = document.createElement('td');
            if (record.latest_payload) {
                const image = document.createElement('img');
                image.className = 'qr-thumb';
                image.id = `qr-thumb-${record.id}`;
                image.alt = `QR code for ${record.person.full_name}`;
                thumbnailCell.append(image);
            } else {
                const icon = document.createElement('span');
                icon.className = 'qr-thumbnail';
                icon.textContent = '▦';
                thumbnailCell.append(icon);
            }
            const personCell = document.createElement('td');
            personCell.append(makePersonLink(record));
            const statusCell = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = `badge ${record.status === 'inactive' || !record.latest_payload ? 'badge-gray' : 'badge-green'}`;
            badge.textContent = record.status === 'inactive' ? 'Inactive' : record.latest_payload ? 'Active' : 'Not Generated';
            statusCell.append(badge);
            const actions = document.createElement('div');
            actions.className = 'row-actions';
            actions.append(createActionButton('View', 'generate', record.id), createActionButton('Edit', 'edit', record.id), createActionButton(record.status === 'inactive' ? 'Restore' : 'Archive', 'archive', record.id, record.status !== 'inactive'));
            const actionCell = document.createElement('td');
            actionCell.append(actions);
            const generatedDate = record.history.at(-1)?.created_at;
            row.append(thumbnailCell, personCell, statusCell, createCell(record.latest_payload ? 'v1.0' : '—'), createCell(generatedDate ? new Date(generatedDate).toLocaleDateString() : '—'), createCell(new Date(record.updated_at).toLocaleDateString()), actionCell);
            qrList.append(row);
        });
        qrEmpty.hidden = visibleRecords.length > 0;
        document.querySelector('#qr-count-summary').textContent = `${visibleRecords.length} QR code${visibleRecords.length === 1 ? '' : 's'}`;
        document.querySelector('#qr-page-label').textContent = `Page ${currentQrPage} of ${pageCount}`;
        if (!qrPanel.hidden) {
            hydrateQrThumbnails(pageRecords.filter((record) => record.latest_payload));
        }
    }

    async function hydrateQrThumbnails(pageRecords) {
        for (const record of pageRecords) {
            const image = document.querySelector(`#qr-thumb-${CSS.escape(record.id)}`);
            if (!image || qrPreviewCache.has(record.id)) {
                if (image && qrPreviewCache.has(record.id)) {
                    image.src = qrPreviewCache.get(record.id);
                }
                continue;
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new URLSearchParams({
                        _token: document.querySelector('meta[name="csrf-token"]').content,
                        ...Object.fromEntries(Object.entries(record.person).map(([field, value]) => [`person[${field}]`, value])),
                        ...Object.fromEntries(record.include.map((field, index) => [`include[${index}]`, field])),
                        error_correction: record.error_correction,
                    }),
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (response.ok) {
                    qrPreviewCache.set(record.id, result.svg);
                    const currentImage = document.querySelector(`#qr-thumb-${CSS.escape(record.id)}`);
                    if (currentImage) {
                        currentImage.src = result.svg;
                    }
                }
            } catch {
                // A missing preview does not affect the saved payload or download flow.
            }
        }
    }

    function renderActivity() {
        const activity = getActivity();
        activityList.replaceChildren();
        activity.forEach((event) => {
            const row = document.createElement('tr');
            row.append(createCell(event.action), createCell(event.person), createCell(new Date(event.created_at).toLocaleString()));
            const result = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = 'badge badge-green';
            badge.textContent = 'Saved locally';
            result.append(badge);
            row.append(result);
            activityList.append(row);
        });
        activityEmpty.hidden = activity.length > 0;

        const dashboardActivity = document.querySelector('#dashboard-recent-activity');
        dashboardActivity.replaceChildren();
        activity.slice(0, 3).forEach((event) => {
            const item = document.createElement('div');
            item.className = 'activity-row';
            const dot = document.createElement('span');
            dot.className = 'activity-dot';
            dot.textContent = '•';
            const details = document.createElement('div');
            const label = document.createElement('strong');
            label.textContent = event.action;
            const person = document.createElement('p');
            person.className = 'muted';
            person.textContent = `${event.person} · ${new Date(event.created_at).toLocaleString()}`;
            details.append(label, person);
            item.append(dot, details);
            dashboardActivity.append(item);
        });
        if (activity.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'muted';
            empty.textContent = 'No local activity has been recorded yet.';
            dashboardActivity.append(empty);
        }
    }

    function renderRecords() {
        renderPeople();
        renderDashboard();
        renderQrCodes();
        renderActivity();
    }

    function clearPersonForm() {
        form.reset();
        recordIdInput.value = '';
        saveLocallyInput.checked = true;
        form.elements.namedItem('error_correction').value = 'Q';
        document.querySelector('#qr-placeholder-heading').hidden = false;
        resultImage.hidden = true;
        resultActions.hidden = true;
        payloadSummary.hidden = true;
        document.querySelector('#form-page-title').textContent = 'Register New Person';
    }

    function showPersonDetails(record) {
        const personName = document.querySelector('#person-details-name');
        personName.textContent = record.person.full_name;
        personName.dataset.recordId = record.id;
        document.querySelector('#person-details-subtitle').textContent = [record.person.designation, record.person.organization, record.person.reference_number].filter(Boolean).join(' · ') || 'Person record';
        const status = document.querySelector('#person-details-status');
        status.textContent = record.status === 'inactive' ? 'Inactive' : 'Active';
        status.className = `badge ${record.status === 'inactive' ? 'badge-gray' : 'badge-green'}`;
        const details = document.querySelector('#person-detail-list');
        details.replaceChildren();
        Object.entries(fieldLabels).forEach(([field, label]) => {
            const value = record.person[field];
            if (!value) {
                return;
            }
            const term = document.createElement('dt');
            term.textContent = label;
            const description = document.createElement('dd');
            description.textContent = value;
            details.append(term, description);
        });
        document.querySelector('#person-details-dialog').showModal();
    }

    function handlePersonAction(event) {
        const button = event.target.closest('button[data-action]');
        if (!button) {
            return;
        }

        const record = records.find((item) => item.id === button.dataset.id);
        if (!record) {
            setLocalStatus('That saved person could not be found.', true);
            return;
        }

        if (button.dataset.action === 'view') {
            showPersonDetails(record);
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

        if (button.dataset.action === 'archive') {
            const status = record.status === 'inactive' ? 'active' : 'inactive';
            const updatedRecord = { ...record, status, updated_at: new Date().toISOString() };
            writeRecords(records.map((current) => current.id === record.id ? updatedRecord : current));
            logActivity(status === 'inactive' ? 'Person archived' : 'Person restored', record.person.full_name);
            renderRecords();
            setLocalStatus(`${record.person.full_name} was ${status === 'inactive' ? 'archived' : 'restored'} in this browser.`);
            return;
        }

        if (button.dataset.action === 'delete' && window.confirm(`Delete ${record.person.full_name} from this browser? This cannot be undone.`)) {
            writeRecords(records.filter((item) => item.id !== record.id));
            logActivity('Person deleted', record.person.full_name);
            renderRecords();
            setLocalStatus(`${record.person.full_name} was removed from this browser.`);
        }
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
        document.querySelector('#form-page-title').textContent = 'Edit Person';
        form.dataset.preparingRecord = 'true';
        document.querySelector('#generate-tab').click();
        delete form.dataset.preparingRecord;
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

    personList.addEventListener('click', handlePersonAction);
    qrList.addEventListener('click', handlePersonAction);
    document.querySelector('#generate-tab').addEventListener('click', () => {
        if (!form.dataset.preparingRecord) {
            clearPersonForm();
        }
    });
    document.querySelector('#qr-tab').addEventListener('click', () => window.setTimeout(renderQrCodes, 0));
    document.querySelectorAll('[data-close-details]').forEach((button) => button.addEventListener('click', () => document.querySelector('#person-details-dialog').close()));
    document.querySelectorAll('[data-dialog-action]').forEach((button) => button.addEventListener('click', () => {
        const record = records.find((item) => item.id === document.querySelector('#person-details-name').dataset.recordId);
        if (!record) {
            return;
        }
        document.querySelector('#person-details-dialog').close();
        if (button.dataset.dialogAction === 'edit') {
            populateForm(record);
        } else {
            populateForm(record);
            form.requestSubmit();
        }
    }));

    document.querySelector('#person-details-dialog').addEventListener('close', () => {
        const name = document.querySelector('#person-details-name');
        delete name.dataset.recordId;
    });

    searchInput.addEventListener('input', () => {
        currentPeoplePage = 1;
        renderPeople();
    });
    document.querySelector('#global-search').addEventListener('input', (event) => {
        searchInput.value = event.target.value;
        currentPeoplePage = 1;
        renderPeople();
    });
    peopleStatusFilter.addEventListener('change', () => {
        currentPeoplePage = 1;
        renderPeople();
    });
    document.querySelectorAll('.people-filter').forEach((button) => button.addEventListener('click', () => {
        activePeopleFilter = button.dataset.filter;
        currentPeoplePage = 1;
        document.querySelectorAll('.people-filter').forEach((filter) => filter.classList.toggle('active', filter === button));
        renderPeople();
    }));
    document.querySelector('#reset-people-filter').addEventListener('click', () => {
        activePeopleFilter = 'all';
        peopleStatusFilter.value = 'all';
        searchInput.value = '';
        document.querySelector('#global-search').value = '';
        document.querySelectorAll('.people-filter').forEach((filter) => filter.classList.toggle('active', filter.dataset.filter === 'all'));
        renderPeople();
    });
    document.querySelectorAll('[data-page]').forEach((button) => button.addEventListener('click', () => {
        currentPeoplePage = Math.max(1, currentPeoplePage + (button.dataset.page === 'next' ? 1 : -1));
        renderPeople();
    }));
    document.querySelectorAll('#qr-search, #qr-status-filter').forEach((input) => ['input', 'change'].forEach((eventName) => input.addEventListener(eventName, () => {
        currentQrPage = 1;
        renderQrCodes();
    })));
    document.querySelectorAll('[data-qr-page]').forEach((button) => button.addEventListener('click', () => {
        currentQrPage = Math.max(1, currentQrPage + (button.dataset.qrPage === 'next' ? 1 : -1));
        renderQrCodes();
    }));
    document.querySelector('#dashboard-range').addEventListener('change', renderDashboard);
    document.querySelector('#export-activity').addEventListener('click', () => {
        const blob = new Blob([JSON.stringify({ schema_version: 1, type: 'qr-manager-local-activity', records: getActivity() }, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = 'qr-manager-activity.json';
        anchor.click();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    });

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
