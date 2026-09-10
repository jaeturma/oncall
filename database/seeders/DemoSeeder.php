<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\Municipality;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Oncall123!';

    public function run(): void
    {
        $this->account('admin@oncall.ph', 'Oncall Administrator', UserRole::Admin, identityVerified: true);
        $this->account('sponsor@oncall.ph', 'Josefa Ramos', UserRole::ServiceFinder);
        $this->account('customer@oncall.ph', 'Maria Dela Cruz', UserRole::ServiceFinder, identityVerified: true);
        $this->account('newcustomer@oncall.ph', 'Ben Alvarez', UserRole::ServiceFinder);

        // Searchable providers: verified profile + verified identity + active services.
        $this->provider('pedro@oncall.ph', 'Pedro Santos', 'Driver', 'Tagum City', 'Davao del Norte', available: true, rating: 4.9, completed: 128, credentials: ['Professional Driver\'s License', 'Defensive driving certificate', '6 years experience']);
        $this->provider('ramon@oncall.ph', 'Ramon Villanueva', 'Electrician', 'Tagum City', 'Davao del Norte', available: true, rating: 4.8, completed: 96, credentials: ['TESDA NC II Electrical Installation', 'PEC-compliant wiring']);
        $this->provider('lito@oncall.ph', 'Lito Bautista', 'Plumber', 'Panabo City', 'Davao del Norte', available: true, rating: 4.6, completed: 54, credentials: ['TESDA NC II Plumbing']);
        $this->provider('grace@oncall.ph', 'Grace Fernandez', 'Babysitter', 'Davao City', 'Davao del Sur', available: true, rating: 4.9, completed: 71, credentials: ['Childcare seminar certificate', 'First-aid trained']);
        $this->provider('noel@oncall.ph', 'Noel Mercado', 'Auto Mechanic', 'Digos City', 'Davao del Sur', available: false, rating: 4.4, completed: 33, credentials: ['TESDA NC II Automotive Servicing']);
        $this->provider('divina@oncall.ph', 'Divina Reyes', 'Tutor', 'Quezon City', 'Metro Manila', available: true, rating: 5.0, completed: 40, credentials: ['Licensed Professional Teacher (LET)']);
        $this->provider('arturo@oncall.ph', 'Arturo Mendoza', 'Carpenter', 'Tagum City', 'Davao del Norte', available: true, rating: 4.3, completed: 18, credentials: ['15 years finishing carpentry']);
        $this->provider('marites@oncall.ph', 'Marites Lim', 'House Cleaning', 'Cebu City', 'Cebu', available: true, rating: 4.7, completed: 62, credentials: ['Bonded and background-checked']);

        // A provider that must NOT appear in search (identity not yet verified).
        $unverified = $this->account('pending@oncall.ph', 'Unverified Applicant', UserRole::ServiceProvider);
        ProviderProfile::query()->updateOrCreate(
            ['user_id' => $unverified->id],
            [
                'province_id' => $this->municipality('Tagum City', 'Davao del Norte')->province_id,
                'municipality_id' => $this->municipality('Tagum City', 'Davao del Norte')->id,
                'bio' => 'Application submitted, awaiting identity review.',
                'available_now' => true,
                'service_radius_km' => 15,
                'verification_status' => VerificationStatus::Pending,
                'rating_cached' => null,
                'completed_jobs_cached' => 0,
            ],
        );
    }

    private function account(string $email, string $name, UserRole $role, bool $identityVerified = false): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => null,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
                'role' => $role,
                'status' => UserStatus::Active,
                'identity_verification_status' => $identityVerified ? VerificationStatus::Verified : VerificationStatus::Pending,
            ],
        );

        if ($identityVerified) {
            $user->providerDocuments()->updateOrCreate(
                ['document_type' => DocumentType::NationalId],
                [
                    'private_path' => 'verification-documents/demo-'.$user->id.'.pdf',
                    'status' => VerificationStatus::Verified,
                    'reviewed_at' => now(),
                    'expires_at' => now()->addYear(),
                ],
            );
        }

        return $user;
    }

    /**
     * @param  list<string>  $credentials
     */
    private function provider(string $email, string $name, string $serviceName, string $municipalityName, string $provinceName, bool $available, float $rating, int $completed, array $credentials): void
    {
        $user = $this->account($email, $name, UserRole::ServiceProvider, identityVerified: true);
        $municipality = $this->municipality($municipalityName, $provinceName);
        $service = Service::query()->where('name', $serviceName)->sole();

        $profile = ProviderProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'province_id' => $municipality->province_id,
                'municipality_id' => $municipality->id,
                'bio' => 'Background-checked '.strtolower($serviceName).' serving '.$municipalityName.' and nearby areas. All bookings, agreements, and payments stay on Oncall Philippines.',
                'available_now' => $available,
                'service_radius_km' => 20,
                'verification_status' => VerificationStatus::Verified,
                'credentials_metadata' => $credentials,
                'rating_cached' => $rating,
                'completed_jobs_cached' => $completed,
            ],
        );

        $profile->providerServices()->updateOrCreate(
            ['service_id' => $service->id],
            [
                'active' => true,
                'experience_text' => $completed.' completed jobs on Oncall',
                'rate_type' => 'per_job',
                'rate_from' => 350,
                'rate_to' => 1500,
            ],
        );
    }

    private function municipality(string $name, string $provinceName): Municipality
    {
        return Municipality::query()
            ->where('name', $name)
            ->whereHas('province', fn ($query) => $query->where('name', $provinceName))
            ->sole();
    }
}
