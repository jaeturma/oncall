<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Province;
use App\Models\User;
use Database\Seeders\ServiceCatalogSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_initial_catalog_is_seeded(): void
    {
        $this->seed(ServiceCatalogSeeder::class);
        $this->assertDatabaseHas('services', ['slug' => 'plumbing']);
    }

    public function test_admin_can_add_a_location_and_guests_can_refine_it(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->post(route('admin.locations.store'), ['province' => 'Cebu', 'municipality' => 'Cebu City', 'type' => 'city'])->assertRedirect();
        $province = Province::where('name', 'Cebu')->firstOrFail();
        $this->get(route('locations.municipalities', $province))->assertOk()->assertJsonFragment(['name' => 'Cebu City']);
    }

    public function test_service_finder_cannot_manage_catalog(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('admin.categories.store'), ['name' => 'Unsafe', 'slug' => 'unsafe'])->assertForbidden();
    }
}
