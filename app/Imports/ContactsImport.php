<?php

namespace App\Imports;

use App\Models\Contact;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContactsImport implements ToCollection, WithHeadingRow
{
    public function __construct(private readonly int $userId) {}

    public int $imported = 0;
    public int $skipped = 0;
    public array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $name = trim((string) ($row['name'] ?? ''));
            $phone = $this->normalizePhone((string) ($row['phone_number'] ?? ''));
            $notes = trim((string) ($row['notes'] ?? ''));

            if ($name === '' || $phone === null) {
                $this->skipped++;
                $this->errors[] = "Row {$line}: invalid or missing name / phone_number.";
                continue;
            }

            $exists = Contact::where('phone_number', $phone)->exists();

            if ($exists) {
                $this->skipped++;
                $this->errors[] = "Row {$line}: phone number already exists.";
                continue;
            }

            Contact::create([
                'name' => $name,
                'phone_number' => $phone,
                'notes' => $notes !== '' ? $notes : null,
                'created_by' => $this->userId,
            ]);

            $this->imported++;
        }
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return preg_match('/^639\d{9}$/', $digits) ? $digits : null;
        }

        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            $digits = '63' . substr($digits, 1);
            return preg_match('/^639\d{9}$/', $digits) ? $digits : null;
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '63' . $digits;
            return preg_match('/^639\d{9}$/', $digits) ? $digits : null;
        }

        return null;
    }
}
