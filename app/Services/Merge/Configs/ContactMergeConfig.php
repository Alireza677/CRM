<?php

namespace App\Services\Merge\Configs;

use App\Models\Contact;
use App\Services\Merge\Contracts\MergeConfig;

class ContactMergeConfig implements MergeConfig
{
    public const ENTITY_TYPE = 'contact';

    public function entityType(): string
    {
        return self::ENTITY_TYPE;
    }

    public function modelClass(): string
    {
        return Contact::class;
    }

    public function mergeableFields(): array
    {
        return [
            'first_name',
            'last_name',
            'position',
            'email',
            'phone',
            'mobile',
            'website',
            'company',
            'state',
            'city',
            'address',
            'organization_id',
            'opportunity_id',
            'assigned_to',
        ];
    }

    public function uniqueFields(): array
    {
        return ['email', 'phone', 'mobile'];
    }

    public function relationDefinitions(): array
    {
        return [
            'direct' => [
                ['table' => 'sales_leads', 'foreign_key' => 'contact_id'],
                ['table' => 'opportunities', 'foreign_key' => 'contact_id'],
                ['table' => 'proformas', 'foreign_key' => 'contact_id'],
                ['table' => 'quotations', 'foreign_key' => 'contact_id'],
            ],
            'pivot' => [
                ['table' => 'lead_contacts', 'foreign_key' => 'contact_id', 'related_key' => 'sales_lead_id'],
                ['table' => 'sms_list_contact', 'foreign_key' => 'contact_id', 'related_key' => 'sms_list_id'],
            ],
            'polymorphic' => [
                [
                    'table' => 'notes',
                    'morph_type_column' => 'noteable_type',
                    'morph_id_column' => 'noteable_id',
                    'morph_class' => Contact::class,
                ],
                [
                    'table' => config('activitylog.table_name', 'activity_log'),
                    'morph_type_column' => 'subject_type',
                    'morph_id_column' => 'subject_id',
                    'morph_class' => Contact::class,
                ],
            ],
        ];
    }
}
