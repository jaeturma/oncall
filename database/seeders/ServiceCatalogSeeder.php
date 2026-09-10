<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceCatalogSeeder extends Seeder
{
    /**
     * The initial Oncall Philippines service catalogue.
     *
     * Services are named after the provider role because the landing-page
     * finder shows them directly ("What service do you need? [ Driver ]").
     *
     * @var array<string, array{sort: int, verification: string, services: list<string>}>
     */
    private const CATALOG = [
        'Household Help' => ['sort' => 1, 'verification' => 'basic', 'services' => ['Babysitter', 'Laundry Service', 'Kitchen Helper', 'House Cleaning', 'Elderly Caregiver']],
        'Skilled Trades' => ['sort' => 2, 'verification' => 'skill', 'services' => ['Plumber', 'Electrician', 'Mason', 'Carpenter', 'Welder', 'Painter']],
        'Transport & Automotive' => ['sort' => 3, 'verification' => 'license', 'services' => ['Driver', 'Auto Mechanic', 'Motorcycle Mechanic']],
        'Technical & Repair' => ['sort' => 4, 'verification' => 'skill', 'services' => ['Computer Technician', 'Appliance Technician', 'Aircon Technician', 'Repair Personnel']],
        'Professional Services' => ['sort' => 5, 'verification' => 'license', 'services' => ['Tutor', 'Legal Service', 'Medical Professional', 'Accountant']],
    ];

    public function run(): void
    {
        foreach (self::CATALOG as $categoryName => $config) {
            $category = ServiceCategory::query()->updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'active' => true, 'sort_order' => $config['sort']],
            );

            foreach ($config['services'] as $serviceName) {
                $category->services()->updateOrCreate(
                    ['slug' => Str::slug($serviceName)],
                    ['name' => $serviceName, 'active' => true, 'verification_level' => $config['verification']],
                );
            }
        }
    }
}
