<?php

namespace Database\Seeders;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\DocumentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\AccountType;
use App\Models\Municipality;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use App\Services\CommissionEngine;
use App\Services\WalletLedger;
use App\Services\WithdrawalWorkflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Oncall123!';

    public function run(): void
    {
        $this->accountTypes();

        $this->account('admin@oncall.ph', 'Oncall Administrator', UserRole::Admin, identityVerified: true);
        $this->account('accounting@oncall.ph', 'Accounting Officer', UserRole::Accounting, identityVerified: true);
        $this->account('budget@oncall.ph', 'Budget Approver', UserRole::Budget, identityVerified: true);
        $this->account('cashier@oncall.ph', 'Cashier Clerk', UserRole::Cashier, identityVerified: true);

        $sponsor = $this->account('sponsor@oncall.ph', 'Josefa Ramos', UserRole::ServiceFinder, identityVerified: true, accountTypeSlug: 'service-finder');
        $this->account('customer@oncall.ph', 'Maria Dela Cruz', UserRole::ServiceFinder, identityVerified: true, accountTypeSlug: 'service-finder');
        $this->account('newcustomer@oncall.ph', 'Ben Alvarez', UserRole::ServiceFinder, accountTypeSlug: 'service-finder');

        // Searchable providers. The first two are sponsored by Josefa, so
        // verifying them posts a sponsor commission.
        $this->provider('pedro@oncall.ph', 'Pedro Santos', 'Driver', 'Tagum City', 'Davao del Norte', available: true, rating: 4.9, completed: 128, credentials: ['Professional Driver\'s License', 'Defensive driving certificate', '6 years experience'], sponsor: $sponsor, accountTypeSlug: 'verified-provider');
        $this->provider('ramon@oncall.ph', 'Ramon Villanueva', 'Electrician', 'Tagum City', 'Davao del Norte', available: true, rating: 4.8, completed: 96, credentials: ['TESDA NC II Electrical Installation', 'PEC-compliant wiring'], sponsor: $sponsor, accountTypeSlug: 'premium-provider');
        $this->provider('lito@oncall.ph', 'Lito Bautista', 'Plumber', 'Panabo City', 'Davao del Norte', available: true, rating: 4.6, completed: 54, credentials: ['TESDA NC II Plumbing'], accountTypeSlug: 'verified-provider');
        $this->provider('grace@oncall.ph', 'Grace Fernandez', 'Babysitter', 'Davao City', 'Davao del Sur', available: true, rating: 4.9, completed: 71, credentials: ['Childcare seminar certificate', 'First-aid trained'], accountTypeSlug: 'verified-provider');
        $this->provider('noel@oncall.ph', 'Noel Mercado', 'Auto Mechanic', 'Digos City', 'Davao del Sur', available: false, rating: 4.4, completed: 33, credentials: ['TESDA NC II Automotive Servicing'], accountTypeSlug: 'verified-provider');
        $this->provider('divina@oncall.ph', 'Divina Reyes', 'Tutor', 'Quezon City', 'Metro Manila', available: true, rating: 5.0, completed: 40, credentials: ['Licensed Professional Teacher (LET)'], accountTypeSlug: 'verified-provider');
        $this->provider('arturo@oncall.ph', 'Arturo Mendoza', 'Carpenter', 'Tagum City', 'Davao del Norte', available: true, rating: 4.3, completed: 18, credentials: ['15 years finishing carpentry'], accountTypeSlug: 'verified-provider');
        $this->provider('marites@oncall.ph', 'Marites Lim', 'House Cleaning', 'Cebu City', 'Cebu', available: true, rating: 4.7, completed: 62, credentials: ['Bonded and background-checked'], accountTypeSlug: 'verified-provider');

        // A provider that must NOT appear in search (identity not yet verified).
        $unverified = $this->account('pending@oncall.ph', 'Unverified Applicant', UserRole::ServiceProvider, accountTypeSlug: 'verified-provider');
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

        $this->demoFinance();
    }

    private function accountTypes(): void
    {
        $types = [
            ['name' => 'Service Finder', 'slug' => 'service-finder', 'registration_fee' => 0, 'sponsor_commission_type' => CommissionType::None, 'sponsor_commission_value' => 0],
            ['name' => 'Verified Provider', 'slug' => 'verified-provider', 'registration_fee' => 500, 'sponsor_commission_type' => CommissionType::Percentage, 'sponsor_commission_value' => 10],
            ['name' => 'Premium Provider', 'slug' => 'premium-provider', 'registration_fee' => 1500, 'sponsor_commission_type' => CommissionType::Fixed, 'sponsor_commission_value' => 250],
        ];

        foreach ($types as $type) {
            AccountType::query()->updateOrCreate(['slug' => $type['slug']], [...$type, 'requires_identity_verification' => true, 'active' => true]);
        }
    }

    private function account(string $email, string $name, UserRole $role, bool $identityVerified = false, ?string $accountTypeSlug = null): User
    {
        $wasVerified = User::query()->where('email', $email)->value('identity_verification_status') === VerificationStatus::Verified;

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
                'account_type_id' => $accountTypeSlug ? AccountType::query()->where('slug', $accountTypeSlug)->value('id') : null,
            ],
        );

        if ($identityVerified) {
            $user->providerDocuments()->updateOrCreate(
                ['document_type' => DocumentType::NationalId],
                ['private_path' => 'verification-documents/demo-'.$user->id.'.pdf', 'status' => VerificationStatus::Verified, 'reviewed_at' => now(), 'expires_at' => now()->addYear()],
            );

            // Model events are disabled during seeding, so trigger the
            // commission the same way the User observer would.
            if (! $wasVerified) {
                app(CommissionEngine::class)->handleUserVerified($user->fresh());
            }
        }

        return $user;
    }

    /**
     * @param  list<string>  $credentials
     */
    private function provider(string $email, string $name, string $serviceName, string $municipalityName, string $provinceName, bool $available, float $rating, int $completed, array $credentials, ?User $sponsor = null, ?string $accountTypeSlug = null): void
    {
        $municipality = $this->municipality($municipalityName, $provinceName);
        $service = Service::query()->where('name', $serviceName)->sole();

        // Create the user first WITHOUT verifying, wire the sponsor + account
        // type, then verify so the commission trigger sees the full picture.
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
                'role' => UserRole::ServiceProvider,
                'status' => UserStatus::Active,
                'sponsor_user_id' => $sponsor?->id,
                'account_type_id' => $accountTypeSlug ? AccountType::query()->where('slug', $accountTypeSlug)->value('id') : null,
            ],
        );

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
            ['active' => true, 'experience_text' => $completed.' completed jobs on Oncall', 'rate_type' => 'per_job', 'rate_from' => 350, 'rate_to' => 1500],
        );

        $wasVerified = $user->getOriginal('identity_verification_status') === VerificationStatus::Verified->value;
        $user->providerDocuments()->updateOrCreate(
            ['document_type' => DocumentType::NationalId],
            ['private_path' => 'verification-documents/demo-'.$user->id.'.pdf', 'status' => VerificationStatus::Verified, 'reviewed_at' => now(), 'expires_at' => now()->addYear()],
        );
        $user->update(['identity_verification_status' => VerificationStatus::Verified]);

        if (! $wasVerified) {
            app(CommissionEngine::class)->handleUserVerified($user->fresh());
        }
    }

    /**
     * Approve one sponsor commission (so the sponsor has a spendable balance and
     * an open withdrawal to review), leave another pending for the admin screen.
     */
    private function demoFinance(): void
    {
        $sponsor = User::query()->where('email', 'sponsor@oncall.ph')->first();
        $admin = User::query()->where('email', 'admin@oncall.ph')->first();
        if ($sponsor === null || $admin === null || $sponsor->withdrawals()->exists()) {
            return;
        }

        $sponsor->commissionsEarned()
            ->where('status', CommissionStatus::Pending)
            ->whereHas('sponsoredUser', fn ($query) => $query->where('email', 'pedro@oncall.ph'))
            ->get()
            ->each(fn ($commission) => app(CommissionEngine::class)->approve($commission, $admin));

        $available = app(WalletLedger::class)->availableBalance($sponsor);
        if (bccomp($available, '30', 2) >= 0) {
            app(WithdrawalWorkflow::class)->request($sponsor, '30.00', 'GCash', 'GCash 0917 000 0002');
        }
    }

    private function municipality(string $name, string $provinceName): Municipality
    {
        return Municipality::query()
            ->where('name', $name)
            ->whereHas('province', fn ($query) => $query->where('name', $provinceName))
            ->sole();
    }
}
