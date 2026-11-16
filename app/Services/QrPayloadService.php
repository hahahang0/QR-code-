<?php

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use stdClass;

class QrPayloadService
{
    public const MAX_BYTES_Q = 1500;

    public const MAX_BYTES_H = 1100;

    /** @var array<string, int> */
    private const OPTIONAL_FIELD_LIMITS = [
        'date_of_birth' => 10,
        'gender' => 40,
        'phone' => 40,
        'email' => 254,
        'address' => 500,
        'organization' => 160,
        'department' => 120,
        'designation' => 120,
        'reference_number' => 80,
        'emergency_contact' => 80,
        'notes' => 800,
    ];

    /**
     * @param  array<string, mixed>  $person
     * @param  list<string>  $includedFields
     *
     * @throws JsonException
     */
    public function create(array $person, array $includedFields = []): string
    {
        $record = ['full_name' => trim((string) $person['full_name'])];

        foreach (array_keys(self::OPTIONAL_FIELD_LIMITS) as $field) {
            if (! in_array($field, $includedFields, true)) {
                continue;
            }

            $value = $person[$field] ?? null;

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $record[$field] = $value;
        }

        $payload = json_encode([
            'version' => 1,
            'type' => 'person',
            'person' => $record,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $this->validate($payload);

        return $payload;
    }

    /**
     * @return array{version: int, type: string, person: array<string, string>}
     *
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function validate(string $payload): array
    {
        try {
            $decoded = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The QR payload is not valid JSON.', previous: $exception);
        }

        if (! $decoded instanceof stdClass) {
            throw new InvalidArgumentException('The QR payload must be a JSON object.');
        }

        $root = get_object_vars($decoded);

        if (array_diff(array_keys($root), ['version', 'type', 'person']) !== [] || count($root) !== 3) {
            throw new InvalidArgumentException('The QR payload has an unsupported structure.');
        }

        if (! is_int($root['version']) || $root['version'] !== 1) {
            throw new InvalidArgumentException('This QR payload version is not supported.');
        }

        if (! is_string($root['type']) || $root['type'] !== 'person') {
            throw new InvalidArgumentException('This QR payload type is not supported.');
        }

        if (! $root['person'] instanceof stdClass) {
            throw new InvalidArgumentException('The person data must be a JSON object.');
        }

        $person = get_object_vars($root['person']);
        $allowedFields = ['full_name', ...array_keys(self::OPTIONAL_FIELD_LIMITS)];

        if (array_diff(array_keys($person), $allowedFields) !== []) {
            throw new InvalidArgumentException('The person data contains unsupported fields.');
        }

        if (! isset($person['full_name']) || ! is_string($person['full_name']) || trim($person['full_name']) === '') {
            throw new InvalidArgumentException('A full name is required in a person QR payload.');
        }

        if (mb_strlen($person['full_name'], 'UTF-8') > 120) {
            throw new InvalidArgumentException('The full name is too long.');
        }

        foreach (self::OPTIONAL_FIELD_LIMITS as $field => $maximumLength) {
            if (! array_key_exists($field, $person)) {
                continue;
            }

            $value = $person[$field];

            if (! is_string($value) || preg_match('//u', $value) !== 1 || mb_strlen($value, 'UTF-8') > $maximumLength) {
                throw new InvalidArgumentException("The {$field} field is invalid.");
            }

            if ($field === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException('The email field is invalid.');
            }

            if ($field === 'date_of_birth') {
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                $dateErrors = DateTimeImmutable::getLastErrors();

                if ($date === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
                    throw new InvalidArgumentException('The date_of_birth field must use YYYY-MM-DD.');
                }
            }
        }

        /** @var array<string, string> $person */
        return [
            'version' => 1,
            'type' => 'person',
            'person' => $person,
        ];
    }

    public function assertWithinCapacity(string $payload, string $errorCorrection): void
    {
        if (! in_array($errorCorrection, ['Q', 'H'], true)) {
            throw new InvalidArgumentException('Use error correction Q or H.');
        }

        $maximumBytes = $errorCorrection === 'H' ? self::MAX_BYTES_H : self::MAX_BYTES_Q;
        $actualBytes = strlen($payload);

        if ($actualBytes <= $maximumBytes) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'This QR payload is %d UTF-8 bytes, above the conservative %s-level limit of %d bytes. Exclude optional fields such as notes, address, organization, emergency contact, reference number, department, designation, phone, or email and try again. No information was truncated.',
            $actualBytes,
            $errorCorrection,
            $maximumBytes,
        ));
    }
}
