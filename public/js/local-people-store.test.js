import test from 'node:test';
import assert from 'node:assert/strict';
import {
    createLocalPeopleStore,
    isValidLocalPersonRecord,
    PEOPLE_STORAGE_KEY,
    upgradeLocalPersonRecord,
} from './local-people-store.js';

const timestamp = '2026-11-16T12:00:00.000Z';

function makeRecord(overrides = {}) {
    const person = { full_name: 'Zoë 李', phone: '+977 980 123 4567' };
    const payload = JSON.stringify({ version: 1, type: 'person', person });

    return {
        id: 'local-person-1',
        person,
        include: ['phone'],
        error_correction: 'Q',
        created_at: timestamp,
        updated_at: timestamp,
        latest_payload: payload,
        history: [{ created_at: timestamp, error_correction: 'Q', payload }],
        ...overrides,
    };
}

function makeStorage(initialValue = null) {
    const values = new Map(initialValue === null ? [] : [[PEOPLE_STORAGE_KEY, initialValue]]);

    return {
        getItem(key) {
            return values.get(key) ?? null;
        },
        setItem(key, value) {
            values.set(key, value);
        },
    };
}

test('accepts Unicode person records and round-trips them through local storage', () => {
    const record = makeRecord();
    const store = createLocalPeopleStore(makeStorage());

    assert.equal(isValidLocalPersonRecord(record), true);
    store.write([record]);
    assert.deepEqual(store.read(), [record]);
});

test('upgrades existing browser records that predate local QR history metadata', () => {
    const oldRecord = {
        id: 'local-person-old',
        person: { full_name: 'Existing Record' },
        include: [],
        error_correction: 'Q',
    };
    const upgraded = upgradeLocalPersonRecord(oldRecord);

    assert.equal(isValidLocalPersonRecord(upgraded), true);
    assert.equal(upgraded.latest_payload, null);
    assert.deepEqual(upgraded.history, []);
});

test('rejects malformed QR payload and nested history before import or storage write', () => {
    const malformed = makeRecord({
        history: [{ created_at: timestamp, error_correction: 'Q', payload: '{bad json' }],
    });

    assert.equal(isValidLocalPersonRecord(malformed), false);
    assert.throws(() => createLocalPeopleStore(makeStorage()).write([malformed]), /valid/);
});

test('rejects duplicate identifiers and damaged stored JSON without overwriting it', () => {
    const record = makeRecord();
    const store = createLocalPeopleStore(makeStorage());

    assert.throws(() => store.write([record, record]), /valid/);

    const corruptedStorage = makeStorage('{broken');
    const corruptedStore = createLocalPeopleStore(corruptedStorage);
    assert.throws(() => corruptedStore.read(), SyntaxError);
    assert.equal(corruptedStorage.getItem(PEOPLE_STORAGE_KEY), '{broken');
});

test('reports browser storage quota errors without losing existing records', () => {
    const storage = makeStorage();
    storage.setItem = () => {
        const error = new Error('full');
        error.name = 'QuotaExceededError';
        throw error;
    };

    assert.throws(() => createLocalPeopleStore(storage).write([makeRecord()]), /storage is full/i);
});
