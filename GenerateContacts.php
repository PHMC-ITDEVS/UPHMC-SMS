<?php

// Run via Tinker:
// php artisan tinker --execute="require base_path('GenerateContacts.php');"
// Or paste the contents directly into: php artisan tinker

use App\Models\Contact;
use App\Models\User;

// ─── Config ───────────────────────────────────────────────────────────────────
$count      = 50;       // Number of contacts to generate
$createdBy  = 1;        // User ID to assign as creator (must exist in users table)
// ──────────────────────────────────────────────────────────────────────────────

// Verify the user exists
$user = User::find($createdBy);
if (! $user) {
    echo "❌ User with ID {$createdBy} not found. Update \$createdBy to a valid user ID.\n";
    return;
}

$firstNames = [
    'Juan', 'Maria', 'Jose', 'Ana', 'Carlo', 'Liza', 'Mark', 'Grace',
    'Ryan', 'Cynthia', 'Paolo', 'Kristine', 'Leo', 'Maricel', 'Jomar',
    'Sheila', 'Aldrin', 'Nica', 'Renz', 'Ella', 'Dante', 'Aileen',
    'Jerome', 'Daisy', 'Arnel', 'Rowena', 'Kevin', 'Charie', 'Ian', 'Luz',
];

$lastNames = [
    'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza',
    'Torres', 'Tan', 'Flores', 'Gonzales', 'Aquino', 'De Leon', 'Ramos',
    'Villanueva', 'Castro', 'Soriano', 'Navarro', 'Lim', 'Dela Cruz',
];

$notesSamples = [
    'Met at a conference last year.',
    'Referred by a colleague.',
    'Old client, prefers SMS updates.',
    'VIP contact — handle with care.',
    'Follow up next quarter.',
    'Interested in bulk pricing.',
    'Do not call before 9AM.',
    null,
    null,
    null, // nulls weighted to appear more often
];

/**
 * Generate a random PH mobile number.
 * Randomly picks either the E.164 format (+639XXXXXXXXX)
 * or the local format (09XXXXXXXXX).
 */
$generatePhoneNumber = function (): string {
    // PH mobile prefixes (2nd digit after 9)
    $prefixes = ['17', '18', '19', '20', '51', '52', '53', '54', '55',
                 '56', '61', '62', '63', '64', '65', '91', '92', '93',
                 '94', '95', '96', '97', '98'];

    $prefix = $prefixes[array_rand($prefixes)];
    $suffix  = str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
    $local   = '9' . $prefix . $suffix; // 10 digits after leading 0/+63

    // Randomly return E.164 (+639...) or local (09...)
    return (random_int(0, 1) === 0)
        ? '+63' . $local
        : '0'   . $local;
};

$generated = 0;
$skipped   = 0;

echo "🚀 Generating {$count} contact(s) assigned to user #{$createdBy} ({$user->name})...\n\n";

for ($i = 0; $i < $count; $i++) {
    $name  = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    $phone = $generatePhoneNumber();
    $notes = $notesSamples[array_rand($notesSamples)];

    // Skip if phone already exists (unique constraint)
    if (Contact::where('phone_number', $phone)->exists()) {
        $skipped++;
        continue;
    }

    Contact::create([
        'name'         => $name,
        'phone_number' => $phone,
        'notes'        => $notes,
        'created_by'   => $createdBy,
    ]);

    echo "  ✅ [{$phone}] {$name}\n";
    $generated++;
}

echo "\n✔ Done. Generated: {$generated} | Skipped (duplicate phone): {$skipped}\n";