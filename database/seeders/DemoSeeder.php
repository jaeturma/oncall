<?php

namespace Database\Seeders;

use App\Enums\AvailabilityStatus;
use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\DisputeCategory;
use App\Enums\DocumentType;
use App\Enums\EnforcementCaseStatus;
use App\Enums\JobStatus;
use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceUrgency;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Enums\ViolationSeverity;
use App\Models\AccountType;
use App\Models\AuditLog;
use App\Models\EnforcementCase;
use App\Models\Job;
use App\Models\Municipality;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\CommissionEngine;
use App\Services\DisputeService;
use App\Services\JobPaymentService;
use App\Services\JobService;
use App\Services\ReviewService;
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
        $this->provider('ramon@oncall.ph', 'Ramon Villanueva', 'Electrician', 'Tagum City', 'Davao del Norte', available: false, rating: 4.8, completed: 96, credentials: ['TESDA NC II Electrical Installation', 'PEC-compliant wiring'], sponsor: $sponsor, accountTypeSlug: 'premium-provider', availabilityStatus: AvailabilityStatus::Busy);
        $this->provider('lito@oncall.ph', 'Lito Bautista', 'Plumber', 'Panabo City', 'Davao del Norte', available: true, rating: 4.6, completed: 54, credentials: ['TESDA NC II Plumbing'], accountTypeSlug: 'verified-provider');
        $this->provider('grace@oncall.ph', 'Grace Fernandez', 'Babysitter', 'Davao City', 'Davao del Sur', available: true, rating: 4.9, completed: 71, credentials: ['Childcare seminar certificate', 'First-aid trained'], accountTypeSlug: 'verified-provider');
        $this->provider('noel@oncall.ph', 'Noel Mercado', 'Auto Mechanic', 'Digos City', 'Davao del Sur', available: false, rating: 4.4, completed: 33, credentials: ['TESDA NC II Automotive Servicing'], accountTypeSlug: 'verified-provider');
        $this->provider('divina@oncall.ph', 'Divina Reyes', 'Tutor', 'Quezon City', 'Metro Manila', available: false, rating: 5.0, completed: 40, credentials: ['Licensed Professional Teacher (LET)'], accountTypeSlug: 'verified-provider', availabilityStatus: AvailabilityStatus::ByAppointment);
        $this->provider('arturo@oncall.ph', 'Arturo Mendoza', 'Carpenter', 'Tagum City', 'Davao del Norte', available: true, rating: 4.3, completed: 18, credentials: ['15 years finishing carpentry'], accountTypeSlug: 'verified-provider');
        $this->provider('marites@oncall.ph', 'Marites Lim', 'House Cleaning', 'Cebu City', 'Cebu', available: true, rating: 4.7, completed: 62, credentials: ['Bonded and background-checked'], accountTypeSlug: 'verified-provider');

        // A provider that must NOT appear in search (identity not yet verified,
        // but has a document sitting in the admin verification queue).
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
        $unverified->providerDocuments()->updateOrCreate(
            ['document_type' => DocumentType::DriversLicense],
            ['private_path' => 'verification-documents/demo-'.$unverified->id.'.pdf', 'status' => VerificationStatus::Submitted],
        );

        $this->demoJobAndEarning();
        $this->demoFinance();
        $this->demoOpenRequests();
        $this->demoSafetyReport();
    }

    /**
     * A completed, paid, released job so a provider has a real wallet balance.
     */
    private function demoJobAndEarning(): void
    {
        $finder = User::query()->where('email', 'customer@oncall.ph')->first();
        $lito = User::query()->where('email', 'lito@oncall.ph')->first();
        $accounting = User::query()->where('email', 'accounting@oncall.ph')->first();
        if ($finder === null || $lito === null || $accounting === null || Job::query()->where('provider_id', $lito->id)->exists()) {
            return;
        }

        $profile = $lito->providerProfile;
        $service = Service::query()->where('name', 'Plumber')->sole();

        $request = ServiceRequest::create([
            'service_finder_id' => $finder->id,
            'requested_provider_id' => $lito->id,
            'service_id' => $service->id,
            'province_id' => $profile->province_id,
            'municipality_id' => $profile->municipality_id,
            'title' => 'Leaking kitchen pipe',
            'description' => 'Under-sink pipe dripping; needs replacement.',
            'urgency' => ServiceUrgency::SameDay,
            'status' => ServiceRequestStatus::Accepted,
            'safety_acknowledged_at' => now(),
        ]);

        $job = Job::create([
            'service_request_id' => $request->id,
            'service_finder_id' => $finder->id,
            'provider_id' => $lito->id,
            'agreed_price' => '1200.00',
            'status' => JobStatus::InProgress,
            'accepted_at' => now()->subDays(2),
            'started_at' => now()->subDay(),
        ]);

        app(JobService::class)->transition($job, $lito, JobStatus::Completed, 'Pipe replaced and tested.');
        $payment = $job->jobPayment;
        app(JobPaymentService::class)->confirmPaid($payment, $finder, 'GCash', 'GCash ref 9931-0022');
        app(JobPaymentService::class)->release($payment->fresh(), $accounting);
        app(ReviewService::class)->create($job->fresh(), $finder, ['rating' => 5, 'comment' => 'Fast, tidy, and explained the fix. Would book again.']);
        app(ReviewService::class)->create($job->fresh(), $lito, ['rating' => 5, 'comment' => 'Clear instructions and prompt payment.']);

        // A second job whose payment is confirmed but not yet released, so the
        // staff Job payments queue has something to act on.
        $grace = User::query()->where('email', 'grace@oncall.ph')->first();
        $graceProfile = $grace->providerProfile;
        $babysitting = Service::query()->where('name', 'Babysitter')->sole();
        $request2 = ServiceRequest::create([
            'service_finder_id' => $finder->id,
            'requested_provider_id' => $grace->id,
            'service_id' => $babysitting->id,
            'province_id' => $graceProfile->province_id,
            'municipality_id' => $graceProfile->municipality_id,
            'title' => 'Evening childminding',
            'urgency' => ServiceUrgency::Scheduled,
            'status' => ServiceRequestStatus::Accepted,
            'safety_acknowledged_at' => now(),
        ]);
        $job2 = Job::create([
            'service_request_id' => $request2->id,
            'service_finder_id' => $finder->id,
            'provider_id' => $grace->id,
            'agreed_price' => '800.00',
            'status' => JobStatus::InProgress,
            'accepted_at' => now()->subDay(),
            'started_at' => now()->subHours(5),
        ]);
        app(JobService::class)->transition($job2, $grace, JobStatus::Completed, 'Watched the kids 6-10pm.');
        app(JobPaymentService::class)->confirmPaid($job2->jobPayment, $finder, 'Cash', 'Paid in cash on pickup');
        app(ReviewService::class)->create($job2->fresh(), $finder, ['rating' => 4, 'comment' => 'Kids were happy. Arrived a few minutes late.']);

        // A third job with an open dispute, so the admin dispute queue and the
        // payment-freeze are visible in the demo.
        $arturo = User::query()->where('email', 'arturo@oncall.ph')->first();
        $arturoProfile = $arturo->providerProfile;
        $carpentry = Service::query()->where('name', 'Carpenter')->sole();
        $request3 = ServiceRequest::create([
            'service_finder_id' => $finder->id,
            'requested_provider_id' => $arturo->id,
            'service_id' => $carpentry->id,
            'province_id' => $arturoProfile->province_id,
            'municipality_id' => $arturoProfile->municipality_id,
            'title' => 'Build a shelf unit',
            'urgency' => ServiceUrgency::Scheduled,
            'status' => ServiceRequestStatus::Accepted,
            'safety_acknowledged_at' => now(),
        ]);
        $job3 = Job::create([
            'service_request_id' => $request3->id,
            'service_finder_id' => $finder->id,
            'provider_id' => $arturo->id,
            'agreed_price' => '2500.00',
            'status' => JobStatus::InProgress,
            'accepted_at' => now()->subDays(3),
            'started_at' => now()->subDays(2),
        ]);
        app(JobService::class)->transition($job3, $arturo, JobStatus::Completed, 'Shelf built.');
        app(JobPaymentService::class)->confirmPaid($job3->jobPayment, $finder, 'GCash', 'GCash ref 7712-3300');
        app(DisputeService::class)->open($job3->fresh(), $finder, DisputeCategory::ServiceNotAsAgreed, 'The shelf is uneven and one bracket is missing. Needs to be fixed or partly refunded.');
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
    private function provider(string $email, string $name, string $serviceName, string $municipalityName, string $provinceName, bool $available, float $rating, int $completed, array $credentials, ?User $sponsor = null, ?string $accountTypeSlug = null, ?AvailabilityStatus $availabilityStatus = null): void
    {
        $municipality = $this->municipality($municipalityName, $provinceName);
        $service = Service::query()->where('name', $serviceName)->sole();
        $status = $availabilityStatus ?? ($available ? AvailabilityStatus::Available : AvailabilityStatus::Offline);

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
                'available_now' => $status->isAvailableNow(),
                'availability_status' => $status,
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

    /**
     * A still-pending request (provider dashboard "incoming") and a
     * cancelled one (customer dashboard history), so both sides of the
     * booking flow have more than one state to look at.
     */
    private function demoOpenRequests(): void
    {
        $finder = User::query()->where('email', 'customer@oncall.ph')->first();
        $marites = User::query()->where('email', 'marites@oncall.ph')->first();
        $noel = User::query()->where('email', 'noel@oncall.ph')->first();
        if ($finder === null || $marites === null || $noel === null || ServiceRequest::query()->where('service_finder_id', $finder->id)->where('requested_provider_id', $marites->id)->exists()) {
            return;
        }

        $maritesProfile = $marites->providerProfile;
        ServiceRequest::create([
            'service_finder_id' => $finder->id,
            'requested_provider_id' => $marites->id,
            'service_id' => Service::query()->where('name', 'House Cleaning')->sole()->id,
            'province_id' => $maritesProfile->province_id,
            'municipality_id' => $maritesProfile->municipality_id,
            'title' => 'Full house deep cleaning',
            'description' => 'Two-bedroom condo, moving out end of the month.',
            'urgency' => ServiceUrgency::Scheduled,
            'needed_at' => now()->addDays(4),
            'budget_min' => 1000,
            'budget_max' => 1800,
            'status' => ServiceRequestStatus::Requested,
        ]);

        $noelProfile = $noel->providerProfile;
        ServiceRequest::create([
            'service_finder_id' => $finder->id,
            'requested_provider_id' => $noel->id,
            'service_id' => Service::query()->where('name', 'Auto Mechanic')->sole()->id,
            'province_id' => $noelProfile->province_id,
            'municipality_id' => $noelProfile->municipality_id,
            'title' => 'Car will not start',
            'description' => 'Found another mechanic sooner.',
            'urgency' => ServiceUrgency::Immediate,
            'status' => ServiceRequestStatus::Cancelled,
        ]);
    }

    /**
     * An off-platform-conduct report that opens an enforcement case, so the
     * admin enforcement queue has an untouched case alongside the disputed
     * job (which already has its own case via the dispute flow).
     */
    private function demoSafetyReport(): void
    {
        $finder = User::query()->where('email', 'customer@oncall.ph')->first();
        $ramon = User::query()->where('email', 'ramon@oncall.ph')->first();
        if ($finder === null || $ramon === null || $finder->reportsMade()->exists()) {
            return;
        }

        $report = $finder->reportsMade()->create([
            'reported_user_id' => $ramon->id,
            'category' => ReportCategory::OffPlatformContact,
            'description' => 'Asked to be paid directly via personal GCash instead of through Oncall.',
            'status' => ReportStatus::Submitted,
        ]);

        $case = EnforcementCase::create([
            'user_id' => $ramon->id,
            'related_report_id' => $report->id,
            'violation_category' => $report->category,
            'severity' => ViolationSeverity::Moderate,
            'status' => EnforcementCaseStatus::Open,
        ]);

        AuditLog::create(['actor_id' => $finder->id, 'event' => 'safety.report_submitted', 'subject_type' => EnforcementCase::class, 'subject_id' => $case->id, 'before_json' => null, 'after_json' => $case->toArray()]);
    }

    private function municipality(string $name, string $provinceName): Municipality
    {
        return Municipality::query()
            ->where('name', $name)
            ->whereHas('province', fn ($query) => $query->where('name', $provinceName))
            ->sole();
    }
}
