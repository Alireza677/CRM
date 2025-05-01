<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

class BreadcrumbService
{
    public function generate()
    {
        $currentRoute = Route::current();
        $items = [];

        // Get route segments
        $segments = explode('/', trim($currentRoute->uri(), '/'));

        // Build breadcrumb items
        $url = '';
        foreach ($segments as $segment) {
            $url .= '/' . $segment;
            
            // Skip numeric segments (like IDs)
            if (is_numeric($segment)) {
                continue;
            }

            // Convert segment to readable title
            $title = $this->formatTitle($segment);

            $items[] = [
                'title' => $title,
                'url' => $url
            ];
        }

        return $items;
    }

    protected function formatTitle($segment)
    {
        // Convert dashes and underscores to spaces
        $title = str_replace(['-', '_'], ' ', $segment);
        
        // Convert to Persian titles if needed
        $persianTitles = [
            'dashboard' => 'داشبورد',
            'marketing' => 'بازاریابی',
            'leads' => 'سرنخ‌های فروش',
            'sales' => 'فروش',
            'opportunities' => 'فرصت‌های فروش',
            'contacts' => 'مخاطبین',
            'organizations' => 'سازمان‌ها',
            'proforma' => 'پیش‌فاکتور',
            'inventory' => 'موجودی',
            'support' => 'پشتیبانی',
            'projects' => 'پروژه‌ها',
            'tools' => 'ابزارها',
            'admin' => 'اداری',
            'documents' => 'اسناد',
            'settings' => 'تنظیمات',
            'customize' => 'شخصی‌سازی'
        ];

        return $persianTitles[strtolower($title)] ?? ucwords($title);
    }
} 