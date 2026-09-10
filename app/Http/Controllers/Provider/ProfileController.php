<?php

namespace App\Http\Controllers\Provider;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProviderProfileRequest;
use App\Http\Requests\UpdateProviderProfileRequest;
use App\Models\Municipality;
use App\Models\ProviderProfile;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === UserRole::ServiceProvider, 403);

        return view('provider.dashboard', ['profile' => $request->user()->providerProfile()->with(['province', 'municipality', 'providerServices.service'])->first()]);
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
