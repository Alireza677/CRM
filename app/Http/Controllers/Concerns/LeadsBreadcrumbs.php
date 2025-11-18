<?php

namespace App\Http\Controllers\Concerns;

trait LeadsBreadcrumbs
{
    /**
     * Build the breadcrumb trail for sales lead pages.
     *
     * @param  array<int, array<string, string>>  $trail
     */
    protected function leadsBreadcrumb(array $trail = [], bool $linkIndex = true): array
    {
        $base = [
            'title' => 'سرنخ‌های فروش',
        ];

        if ($linkIndex) {
            $base['url'] = route('marketing.leads.index');
        }

        return array_merge([$base], $trail);
    }
}
