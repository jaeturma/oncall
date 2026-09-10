<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Home Repair' => ['Plumbing', 'Electrical', 'Carpentry'], 'Personal Care' => ['Caregiving', 'Massage'], 'Transport' => ['Delivery', 'Vehicle Assistance']] as $categoryName => $services) {
            $category = ServiceCategory::firstOrCreate(['slug' => Str::slug($categoryName)], ['name' => $categoryName]);
            foreach ($services as $service) {
                $category->services()->firstOrCreate(['slug' => Str::slug($service)], ['name' => $service]);
            }
        }
    }
}
