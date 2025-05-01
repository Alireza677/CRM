<?php

namespace Database\Factories;

use App\Models\SalesLead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesLeadFactory extends Factory
{
    protected $model = SalesLead::class;

    public function definition()
    {
        $farsiFirstNames = ['محمد', 'علی', 'رضا', 'حسین', 'فاطمه', 'زهرا', 'مریم', 'سارا'];
        $farsiLastNames = ['محمدی', 'حسینی', 'رضایی', 'کریمی', 'مهدوی', 'قاسمی', 'اکبری', 'نوری'];
        $farsiCompanies = ['شرکت فناوری اطلاعات', 'گروه صنعتی', 'شرکت بازرگانی', 'مجتمع تجاری', 'شرکت خدماتی'];
        $farsiSources = ['وب سایت', 'نمایشگاه', 'معرفی', 'تبلیغات', 'سایر'];
        $farsiStatuses = ['تماس اولیه', 'موکول به آینده', 'در حال پیگیری', 'تبدیل شده', 'از دست رفته'];

        return [
            'first_name' => $this->faker->randomElement($farsiFirstNames),
            'last_name' => $this->faker->randomElement($farsiLastNames),
            'company' => $this->faker->randomElement($farsiCompanies),
            'email' => $this->faker->unique()->safeEmail,
            'phone' => '09' . $this->faker->numerify('########'),
            'mobile' => '09' . $this->faker->numerify('########'),
            'lead_source' => $this->faker->randomElement($farsiSources),
            'lead_status' => $this->faker->randomElement($farsiStatuses),
            'notes' => $this->faker->realText(200),
            'lead_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'next_follow_up_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'created_by' => User::factory(),
            'assigned_to' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
} 