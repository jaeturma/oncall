<?php

namespace App\Http\Controllers\Provider;

use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProviderProfileRequest;
use App\Http\Requests\UpdateProviderProfileRequest;
use App\Models\Municipality;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use App\Services\WalletLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request, WalletLedger $ledger): View
    {
        abort_unless($request->user()->role === UserRole::ServiceProvider, 403);
        $user = $request->user();
        $openStatuses = [ServiceRequestStatus::Requested, ServiceRequestStatus::Searching];
        $profile = $user->providerProfile()->with(['province', 'municipality', 'providerServices.service'])->first();

        return view('provider.dashboard', [
            'profile' => $profile,
            'identityVerified' => $user->isIdentityVerified(),
            'onboarding' => $this->onboardingItems($user, $profile),
            'metrics' => [
                'new_requests' => $user->requestedServiceRequests()->whereIn('status', $openStatuses)->count(),
                'active_jobs' => $user->providerJobs()->whereIn('status', [JobStatus::Accepted, JobStatus::OnTheWay, JobStatus::InProgress])->count(),
                'completed_jobs' => $user->providerJobs()->where('status', JobStatus::Completed)->count(),
                'earnings_available' => $ledger->availableBalance($user),
            ],
            'incomingRequests' => $user->requestedServiceRequests()->with(['service:id,name', 'municipality:id,name', 'province:id,name'])->whereIn('status', $openStatuses)->latest()->limit(5)->get(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->role === UserRole::ServiceProvider && ! $request->user()->providerProfile()->exists(), 403);

        return view('provider.profile-form', $this->formData());
    }

    public function store(StoreProviderProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $data) {
            $profile = $request->user()->providerProfile()->create($this->profileAttributes($data));
            $this->replaceServices($profile, $data['service_ids']);
        });

        return redirect()->route('provider.dashboard')->with('status', 'Provider profile created.');
    }

    public function edit(UpdateProviderProfileRequest $request, ProviderProfile $providerProfile): View
    {
        return view('provider.profile-form', $this->formData($providerProfile->load('providerServices')));
    }

    public function update(UpdateProviderProfileRequest $request, ProviderProfile $providerProfile): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($providerProfile, $data) {
            $providerProfile->update($this->profileAttributes($data));
            $this->replaceServices($providerProfile, $data['service_ids']);
        });

        return redirect()->route('provider.dashboard')->with('status', 'Provider profile updated.');
    }

    /**
     * @return list<array{label: string, description: string, done: bool, href: string, cta: string, audience: string}>
     */
    private function onboardingItems(User $user, ?ProviderProfile $profile): array
    {
        return [
            ['label' => 'Verify your email', 'description' => 'Confirm the link Oncall sent to '.$user->email.'.', 'done' => $user->hasVerifiedEmail(), 'href' => route('verification.index'), 'cta' => 'Verify email', 'audience' => 'help'],
            ['label' => 'Verify your mobile number', 'description' => 'A quick code confirms it\'s really you.', 'done' => $user->isMobileVerified(), 'href' => route('verification.index'), 'cta' => 'Verify mobile', 'audience' => 'help'],
            ['label' => 'Create your provider profile', 'description' => 'Tell customers what you do and where you work.', 'done' => $profile !== null, 'href' => route('provider.profiles.create'), 'cta' => 'Create profile', 'audience' => 'help'],
            ['label' => 'Verify your identity', 'description' => 'Required before your profile appears in search results.', 'done' => $user->isIdentityVerified(), 'href' => route('verification.index'), 'cta' => 'Verify identity', 'audience' => 'help'],
        ];
    }

    private function formData(?ProviderProfile $profile = null): array
    {
        return ['profile' => $profile, 'services' => Service::where('active', true)->orderBy('name')->get(), 'municipalities' => Municipality::with('province')->orderBy('name')->get()];
    }

    private function replaceServices(ProviderProfile $profile, array $serviceIds): void
    {
        $profile->providerServices()->delete();
        $profile->providerServices()->createMany(collect($serviceIds)->map(fn (int $serviceId): array => ['service_id' => $serviceId])->all());
    }

    private function profileAttributes(array $data): array
    {
        $attributes = Arr::except($data, 'service_ids');
        $credentials = array_values(array_filter($attributes['credentials_metadata'] ?? [], fn (?string $credential): bool => filled($credential)));
        $attributes['credentials_metadata'] = $credentials === [] ? null : $credentials;

        return $attributes;
    }
}
