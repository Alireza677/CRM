<?php

namespace App\Imports;

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContactsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $organizationName = trim($row['company'] ?? '');
        $organizationId = null;

        if (!empty($organizationName)) {
            $organization = Organization::firstOrCreate(
                ['name' => $organizationName],
                [
                    'slug'  => Str::slug($organizationName),
                    'phone' => $row['organization_phone'] ?? null,
                    'city'  => $row['city'] ?? null,
                ]
            );
            $organizationId = $organization->id;
        }

        return new Contact([
            'first_name'      => $row['first_name'] ?? '',
            'last_name'       => $row['last_name'] ?? '',
            'phone'           => $row['phone'] ?? null,
            'mobile'          => $row['mobile'] ?? null,
            'city'            => $row['city'] ?? null,
            'organization_id' => $organizationId,
        ]);
    }
}
