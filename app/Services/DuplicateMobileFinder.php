<?php

namespace App\Services;

use App\Helpers\DateHelper;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Proforma;
use App\Models\SalesLead;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DuplicateMobileFinder
{
    public const TYPE_CONTACT = 'contact';
    public const TYPE_ORGANIZATION = 'organization';
    public const TYPE_LEAD = 'lead';
    public const TYPE_OPPORTUNITY = 'opportunity';
    public const TYPE_PROFORMA = 'proforma';
    public const TYPE_CUSTOMER = 'customer';
    public const TYPE_SUPPLIER = 'supplier';
    public const TYPE_USER = 'user';

    /**
     * Returns the first matching record by priority.
     *
     * @return array{record: Model, type: string, mobile: string}|null
     */
    public function find(string $normalizedMobile): ?array
    {
        $normalized = $this->normalizeMobile($normalizedMobile);
        if (!$normalized) {
            return null;
        }

        $checks = [
            [self::TYPE_CONTACT, Contact::class, ['mobile', 'phone']],
            [self::TYPE_ORGANIZATION, Organization::class, ['phone']],
            [self::TYPE_LEAD, SalesLead::class, ['mobile', 'phone']],
        ];

        foreach ($checks as [$type, $modelClass, $fields]) {
            $record = $this->findInModel($modelClass, $fields, $normalized);
            if ($record) {
                return ['record' => $record, 'type' => $type, 'mobile' => $normalized];
            }
        }

        $opportunity = $this->findOpportunityByMobile($normalized);
        if ($opportunity) {
            return ['record' => $opportunity, 'type' => self::TYPE_OPPORTUNITY, 'mobile' => $normalized];
        }

        $proforma = $this->findProformaByMobile($normalized);
        if ($proforma) {
            return ['record' => $proforma, 'type' => self::TYPE_PROFORMA, 'mobile' => $normalized];
        }

        $otherChecks = [
            [self::TYPE_CUSTOMER, Customer::class, ['phone']],
            [self::TYPE_SUPPLIER, Supplier::class, ['phone']],
            [self::TYPE_USER, User::class, ['mobile']],
        ];

        foreach ($otherChecks as [$type, $modelClass, $fields]) {
            $record = $this->findInModel($modelClass, $fields, $normalized);
            if ($record) {
                return ['record' => $record, 'type' => $type, 'mobile' => $normalized];
            }
        }

        return null;
    }

    public function findLeadByMobile(string $normalizedMobile): ?SalesLead
    {
        $normalized = $this->normalizeMobile($normalizedMobile);
        if (!$normalized) {
            return null;
        }

        $record = $this->findInModel(SalesLead::class, ['mobile', 'phone'], $normalized);

        return $record instanceof SalesLead ? $record : null;
    }

    public function findContactByMobile(string $normalizedMobile): ?Contact
    {
        $normalized = $this->normalizeMobile($normalizedMobile);
        if (!$normalized) {
            return null;
        }

        $record = $this->findInModel(Contact::class, ['mobile', 'phone'], $normalized);

        return $record instanceof Contact ? $record : null;
    }

    public function buildModalPayload(Model $record, string $type, string $normalizedMobile, array $extra = []): array
    {
        $config = $this->moduleConfig($type);
        $title = $this->resolveTitle($record, $type);
        $mobile = $this->resolveMobileValue($record, $type) ?? $normalizedMobile;
        $createdAt = $record->created_at ? $record->created_at->toDateTimeString() : null;

        return [
            'type' => $type,
            'module_fa' => $config['module_fa'] ?? $type,
            'model' => class_basename($record),
            'record_id' => $record->id,
            'title' => $title,
            'mobile' => $mobile,
            'created_at' => $createdAt,
            'created_at_fa' => $record->created_at ? DateHelper::toJalali($record->created_at) : null,
            'show_url' => $this->resolveShowUrl($record, $config['show_route'] ?? null),
            'index_url' => $this->resolveIndexUrl($config['index_route'] ?? null),
            'extra' => [
                'status' => $this->resolveStatus($record, $type),
                'source' => $this->resolveSource($record, $type),
            ],
            'meta' => $extra,
        ];
    }

    public function buildAlertPayload(Model $record, string $type, string $normalizedMobile): array
    {
        return $this->buildModalPayload($record, $type, $normalizedMobile);
    }

    public function normalizeMobile(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        static $digitMap = [
            "\u{06F0}" => '0', "\u{06F1}" => '1', "\u{06F2}" => '2', "\u{06F3}" => '3', "\u{06F4}" => '4',
            "\u{06F5}" => '5', "\u{06F6}" => '6', "\u{06F7}" => '7', "\u{06F8}" => '8', "\u{06F9}" => '9',
            "\u{0660}" => '0', "\u{0661}" => '1', "\u{0662}" => '2', "\u{0663}" => '3', "\u{0664}" => '4',
            "\u{0665}" => '5', "\u{0666}" => '6', "\u{0667}" => '7', "\u{0668}" => '8', "\u{0669}" => '9',
        ];

        $value = strtr($value, $digitMap);
        $value = preg_replace('/[^\d+]/u', '', $value) ?? '';
        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '098')) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            $digits = substr($digits, -10);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0' . $digits;
        } elseif (strlen($digits) > 11) {
            $lastTen = substr($digits, -10);
            if ($lastTen !== false && strlen($lastTen) === 10 && str_starts_with($lastTen, '9')) {
                $digits = '0' . $lastTen;
            }
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            return $digits;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }

    private function findInModel(string $modelClass, array $fields, string $normalized): ?Model
    {
        $variants = $this->mobileComparisonVariants($normalized);
        if (empty($variants)) {
            return null;
        }

        $query = $modelClass::query()->withoutGlobalScopes();
        $this->applyMobileRegexConditions($query, $fields, $variants);

        $candidates = $query->limit(20)->get();
        foreach ($candidates as $candidate) {
            foreach ($fields as $field) {
                $value = $candidate->{$field} ?? null;
                if (!$value) {
                    continue;
                }
                if ($this->normalizeMobile($value) === $normalized) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function findOpportunityByMobile(string $normalized): ?Opportunity
    {
        $variants = $this->mobileComparisonVariants($normalized);
        if (empty($variants)) {
            return null;
        }

        $query = Opportunity::query()
            ->withoutGlobalScopes()
            ->with(['contact', 'organization']);

        $query->where(function (Builder $q) use ($variants) {
            $q->whereHas('contact', function (Builder $c) use ($variants) {
                $this->applyMobileRegexConditions($c, ['mobile', 'phone'], $variants);
            })->orWhereHas('organization', function (Builder $o) use ($variants) {
                $this->applyMobileRegexConditions($o, ['phone'], $variants);
            });
        });

        $candidates = $query->limit(20)->get();
        foreach ($candidates as $candidate) {
            $contact = $candidate->contact;
            if ($contact && $this->normalizeMobile($contact->mobile ?? $contact->phone) === $normalized) {
                return $candidate;
            }
            $organization = $candidate->organization;
            if ($organization && $this->normalizeMobile($organization->phone) === $normalized) {
                return $candidate;
            }
        }

        return null;
    }

    private function findProformaByMobile(string $normalized): ?Proforma
    {
        $variants = $this->mobileComparisonVariants($normalized);
        if (empty($variants)) {
            return null;
        }

        $query = Proforma::query()
            ->withoutGlobalScopes()
            ->with(['contact', 'organization']);

        $query->where(function (Builder $q) use ($variants) {
            $q->whereHas('contact', function (Builder $c) use ($variants) {
                $this->applyMobileRegexConditions($c, ['mobile', 'phone'], $variants);
            })->orWhereHas('organization', function (Builder $o) use ($variants) {
                $this->applyMobileRegexConditions($o, ['phone'], $variants);
            });
        });

        $candidates = $query->limit(20)->get();
        foreach ($candidates as $candidate) {
            $contact = $candidate->contact;
            if ($contact && $this->normalizeMobile($contact->mobile ?? $contact->phone) === $normalized) {
                return $candidate;
            }
            $organization = $candidate->organization;
            if ($organization && $this->normalizeMobile($organization->phone) === $normalized) {
                return $candidate;
            }
        }

        return null;
    }

    private function applyMobileRegexConditions(Builder $query, array $fields, array $variants): void
    {
        $query->where(function (Builder $q) use ($fields, $variants) {
            $applied = false;
            foreach ($fields as $field) {
                foreach ($variants as $digits) {
                    $pattern = $this->buildMobileRegexFromDigits($digits);
                    if ($pattern === '') {
                        continue;
                    }
                    $applied = true;
                    $q->orWhereRaw("{$field} REGEXP ?", [$pattern]);
                }
            }

            if (!$applied) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    private function mobileComparisonVariants(string $normalized): array
    {
        $digits = preg_replace('/\D+/', '', $normalized) ?? '';
        if ($digits === '') {
            return [];
        }

        $variants = [$digits];
        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            $withoutZero = substr($digits, 1);
            $variants[] = $withoutZero;
            $variants[] = '98' . $withoutZero;
            $variants[] = '098' . $withoutZero;
            $variants[] = '0098' . $withoutZero;
        } else {
            $variants[] = ltrim($digits, '0');
        }

        return array_values(array_unique(array_filter($variants)));
    }

    private function buildMobileRegexFromDigits(string $digits): string
    {
        $parts = preg_split('//u', $digits, -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return '';
        }

        $escaped = array_map(static fn (string $part) => preg_quote($part, '/'), $parts);

        return implode('[^0-9]*', $escaped);
    }

    private function moduleConfig(string $type): array
    {
        return match ($type) {
            self::TYPE_CONTACT => [
                'module_fa' => 'مخاطب',
                'show_route' => 'sales.contacts.show',
                'index_route' => 'sales.contacts.index',
            ],
            self::TYPE_ORGANIZATION => [
                'module_fa' => 'سازمان',
                'show_route' => 'sales.organizations.show',
                'index_route' => 'sales.organizations.index',
            ],
            self::TYPE_LEAD => [
                'module_fa' => 'سرنخ',
                'show_route' => 'marketing.leads.show',
                'index_route' => 'marketing.leads.index',
            ],
            self::TYPE_OPPORTUNITY => [
                'module_fa' => 'فرصت',
                'show_route' => 'sales.opportunities.show',
                'index_route' => 'sales.opportunities.index',
            ],
            self::TYPE_PROFORMA => [
                'module_fa' => 'پیش‌فاکتور',
                'show_route' => 'sales.proformas.show',
                'index_route' => 'sales.proformas.index',
            ],
            self::TYPE_CUSTOMER => [
                'module_fa' => 'مشتری',
                'show_route' => 'customers.show',
                'index_route' => 'customers.index',
            ],
            self::TYPE_SUPPLIER => [
                'module_fa' => 'تامین‌کننده',
                'show_route' => 'inventory.suppliers.show',
                'index_route' => 'inventory.suppliers.index',
            ],
            self::TYPE_USER => [
                'module_fa' => 'کاربر',
                'show_route' => 'settings.users.edit',
                'index_route' => 'settings.users.index',
            ],
            default => [],
        };
    }

    private function resolveShowUrl(Model $record, ?string $routeName): ?string
    {
        if (!$routeName) {
            return null;
        }

        return route($routeName, $record);
    }

    private function resolveIndexUrl(?string $routeName): ?string
    {
        if (!$routeName) {
            return null;
        }

        return route($routeName);
    }

    private function resolveTitle(Model $record, string $type): string
    {
        if (method_exists($record, 'getNotificationTitle')) {
            $title = $record->getNotificationTitle();
            if (is_string($title) && $title !== '') {
                return $title;
            }
        }

        return match ($type) {
            self::TYPE_CONTACT => trim((string) $record->first_name . ' ' . (string) $record->last_name),
            self::TYPE_ORGANIZATION => (string) ($record->name ?? ''),
            self::TYPE_LEAD => (string) ($record->full_name ?? $record->company ?? ''),
            self::TYPE_OPPORTUNITY => (string) ($record->name ?? ''),
            self::TYPE_PROFORMA => (string) ($record->subject ?? ''),
            self::TYPE_CUSTOMER => (string) ($record->name ?? ''),
            self::TYPE_SUPPLIER => (string) ($record->name ?? ''),
            self::TYPE_USER => (string) ($record->name ?? ''),
            default => '',
        };
    }

    private function resolveMobileValue(Model $record, string $type): ?string
    {
        return match ($type) {
            self::TYPE_CONTACT => $record->mobile ?? $record->phone ?? null,
            self::TYPE_ORGANIZATION => $record->phone ?? null,
            self::TYPE_LEAD => $record->mobile ?? $record->phone ?? null,
            self::TYPE_OPPORTUNITY => $record->contact?->mobile ?? $record->contact?->phone ?? $record->organization?->phone ?? null,
            self::TYPE_PROFORMA => $record->contact?->mobile ?? $record->contact?->phone ?? $record->organization?->phone ?? null,
            self::TYPE_CUSTOMER => $record->phone ?? null,
            self::TYPE_SUPPLIER => $record->phone ?? null,
            self::TYPE_USER => $record->mobile ?? null,
            default => null,
        };
    }

    private function resolveStatus(Model $record, string $type): ?string
    {
        return match ($type) {
            self::TYPE_LEAD => $record->lead_status ?? $record->status ?? null,
            self::TYPE_OPPORTUNITY => $record->stage ?? null,
            self::TYPE_PROFORMA => $record->approval_stage ?? $record->proforma_stage ?? null,
            default => null,
        };
    }

    private function resolveSource(Model $record, string $type): ?string
    {
        return match ($type) {
            self::TYPE_LEAD => $record->lead_source ?? null,
            self::TYPE_OPPORTUNITY => $record->source ?? null,
            default => null,
        };
    }
}
