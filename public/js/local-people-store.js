export const PEOPLE_STORAGE_KEY = 'qr-manager.people.v1';
export const MAXIMUM_LOCAL_PEOPLE = 500;

export const PERSON_FIELD_LIMITS = Object.freeze({
    full_name: 120,
    date_of_birth: 10,
    gender: 40,
    phone: 40,
    email: 254,
    address: 500,
    organization: 160,
    department: 120,
    designation: 120,
    reference_number: 80,
    emergency_contact: 80,
    notes: 800,
});

export const OPTIONAL_PERSON_FIELDS = Object.freeze(Object.keys(PERSON_FIELD_LIMITS).filter((field) => field !== 'full_name'));

function isValidDate(value) {
    if (value === '') {
        return true;
    }

    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    const parsed = match ? new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3]))) : null;

    return Boolean(parsed && parsed.toISOString().slice(0, 10) === value);
}

function isValidTimestamp(value) {
    if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/u.test(value)) {
        return false;
    }

    try {
        return new Date(value).toISOString() === value;
    } catch {
        return false;
    }
}

function isValidPayload(payload, expectedPerson = null) {
    if (typeof payload !== 'string' || new TextEncoder().encode(payload).length > 1500) {
        return false;
    }

    try {
        const decoded = JSON.parse(payload);

        if (!decoded || Array.isArray(decoded) || typeof decoded !== 'object') {
            return false;
        }

        const rootKeys = Object.keys(decoded).sort();
        const person = decoded.person;

        if (rootKeys.join(',') !== 'person,type,version'
            || decoded.version !== 1
            || decoded.type !== 'person'
            || !person
            || Array.isArray(person)
            || typeof person !== 'object'
            || Object.keys(person).some((field) => !Object.prototype.hasOwnProperty.call(PERSON_FIELD_LIMITS, field))) {
            return false;
        }

        if (typeof person.full_name !== 'string'
            || person.full_name.trim() === ''
            || [...person.full_name].length > PERSON_FIELD_LIMITS.full_name) {
            return false;
        }

        for (const [field, value] of Object.entries(person)) {
            if (typeof value !== 'string' || [...value].length > PERSON_FIELD_LIMITS[field]) {
                return false;
            }

            if (expectedPerson && expectedPerson[field] !== value) {
                return false;
            }
        }

        return !expectedPerson || expectedPerson.full_name === person.full_name;
    } catch {
        return false;
    }
}

export function isValidLocalPersonRecord(record) {
    if (!record || typeof record !== 'object' || Array.isArray(record)) {
        return false;
    }

    if (typeof record.id !== 'string' || record.id.length < 1 || record.id.length > 100) {
        return false;
    }

    if (!record.person || typeof record.person !== 'object' || Array.isArray(record.person)) {
        return false;
    }

    if (Object.keys(record.person).some((field) => !Object.prototype.hasOwnProperty.call(PERSON_FIELD_LIMITS, field))) {
        return false;
    }

    if (typeof record.person.full_name !== 'string' || record.person.full_name.trim() === '' || [...record.person.full_name].length > PERSON_FIELD_LIMITS.full_name) {
        return false;
    }

    for (const [field, value] of Object.entries(record.person)) {
        if (typeof value !== 'string' || [...value].length > PERSON_FIELD_LIMITS[field]) {
            return false;
        }
    }

    if (!isValidDate(record.person.date_of_birth || '')) {
        return false;
    }

    if (record.person.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/u.test(record.person.email)) {
        return false;
    }

    return Array.isArray(record.include)
        && new Set(record.include).size === record.include.length
        && record.include.every((field) => OPTIONAL_PERSON_FIELDS.includes(field))
        && (record.error_correction === 'Q' || record.error_correction === 'H')
        && isValidTimestamp(record.created_at)
        && isValidTimestamp(record.updated_at)
        && (record.latest_payload === null || isValidPayload(record.latest_payload, record.person))
        && Array.isArray(record.history)
        && record.history.length <= 10
        && record.history.every((version) => version
            && isValidTimestamp(version.created_at)
            && ['Q', 'H'].includes(version.error_correction)
            && isValidPayload(version.payload));
}

export function upgradeLocalPersonRecord(record) {
    if (!record || typeof record !== 'object' || Array.isArray(record)) {
        return null;
    }

    const now = new Date().toISOString();
    const upgraded = {
        ...record,
        created_at: record.created_at || now,
        updated_at: record.updated_at || record.created_at || now,
        latest_payload: record.latest_payload ?? null,
        history: record.history ?? [],
    };

    return isValidLocalPersonRecord(upgraded) ? upgraded : null;
}

export function createLocalPeopleStore(storage) {
    if (!storage || typeof storage.getItem !== 'function' || typeof storage.setItem !== 'function') {
        throw new TypeError('A browser-compatible storage object is required.');
    }

    return Object.freeze({
        read() {
            const raw = storage.getItem(PEOPLE_STORAGE_KEY);
            const parsed = raw === null ? [] : JSON.parse(raw);
            const records = Array.isArray(parsed) ? parsed.map(upgradeLocalPersonRecord) : parsed;

            if (!Array.isArray(records)
                || records.length > MAXIMUM_LOCAL_PEOPLE
                || records.some((record) => record === null)
                || records.some((record) => !isValidLocalPersonRecord(record))
                || new Set(records.map((record) => record.id)).size !== records.length) {
                throw new Error('Saved records have an unsupported or damaged format. They were not changed.');
            }

            return records;
        },
        write(records) {
            if (!Array.isArray(records)
                || records.length > MAXIMUM_LOCAL_PEOPLE
                || records.some((record) => !isValidLocalPersonRecord(record))
                || new Set(records.map((record) => record.id)).size !== records.length) {
                throw new Error(`Records must be valid and no more than ${MAXIMUM_LOCAL_PEOPLE} people can be stored.`);
            }

            try {
                storage.setItem(PEOPLE_STORAGE_KEY, JSON.stringify(records));
            } catch (error) {
                if (error?.name === 'QuotaExceededError') {
                    throw new Error('Browser storage is full. Export these records and remove some before saving more.');
                }

                throw error;
            }

            return records;
        },
    });
}
