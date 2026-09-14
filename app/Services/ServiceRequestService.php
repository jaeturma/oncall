<?php

namespace App\Services;

use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\Job;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

class ServiceRequestService
{
    public function __construct(private readonly NotificationDispatcher $notifications) {}

    public function create(User $serviceFinder, ProviderProfile $providerProfile, array $attributes): ServiceRequest
    {
        unset($attributes['safety_acknowledged']);

        $serviceRequest = $serviceFinder->serviceRequests()->create([
            ...$attributes,
            'requested_provider_id' => $providerProfile->user_id,
            'status' => ServiceRequestStatus::Requested,
            'safety_acknowledged_at' => now(),
        ]);

        $this->notifications->dispatch(
            $providerProfile->user,
            'service_request_created',
            ['customer_name' => $serviceFinder->name, 'service_name' => $serviceRequest->title],
            ['screen' => 'service_request', 'id' => $serviceRequest->id],
            dedupKey: "service_request_created:service_request:{$serviceRequest->id}",
        );

        return $serviceRequest;
    }

    public function accept(ServiceRequest $serviceRequest, User $provider, string $agreedPrice): Job
    {
        return DB::transaction(function () use ($serviceRequest, $provider, $agreedPrice): Job {
            $lockedRequest = ServiceRequest::whereKey($serviceRequest)->lockForUpdate()->firstOrFail();
            abort_unless($lockedRequest->status === ServiceRequestStatus::Requested && $lockedRequest->requested_provider_id === $provider->id, 409);
            $lockedRequest->update(['status' => ServiceRequestStatus::Accepted]);

            $job = Job::create([
                'service_request_id' => $lockedRequest->id,
                'service_finder_id' => $lockedRequest->service_finder_id,
                'provider_id' => $provider->id,
                'agreed_price' => $agreedPrice,
                'status' => JobStatus::Accepted,
                'accepted_at' => now(),
            ]);
            $job->statusLogs()->create(['from_status' => null, 'to_status' => JobStatus::Accepted, 'changed_by' => $provider->id, 'notes' => 'Provider accepted the service request.']);

            $this->notifications->dispatch(
                $lockedRequest->serviceFinder,
                'service_request_accepted',
                ['provider_name' => $provider->name, 'service_name' => $lockedRequest->title, 'amount' => $agreedPrice],
                ['screen' => 'job', 'id' => $job->id],
                dedupKey: "service_request_accepted:service_request:{$lockedRequest->id}",
            );

            return $job;
        });
    }

    public function decline(ServiceRequest $serviceRequest, User $provider): ServiceRequest
    {
        return DB::transaction(function () use ($serviceRequest, $provider): ServiceRequest {
            $lockedRequest = ServiceRequest::whereKey($serviceRequest)->lockForUpdate()->firstOrFail();
            abort_unless($lockedRequest->status === ServiceRequestStatus::Requested && $lockedRequest->requested_provider_id === $provider->id, 409);
            $lockedRequest->update(['requested_provider_id' => null, 'status' => ServiceRequestStatus::Searching]);

            $this->notifications->dispatch(
                $lockedRequest->serviceFinder,
                'service_request_declined',
                ['service_name' => $lockedRequest->title],
                ['screen' => 'service_request', 'id' => $lockedRequest->id],
                dedupKey: "service_request_declined:service_request:{$lockedRequest->id}",
            );

            return $lockedRequest;
        });
    }

    public function cancel(ServiceRequest $serviceRequest, User $serviceFinder): ServiceRequest
    {
        return DB::transaction(function () use ($serviceRequest, $serviceFinder): ServiceRequest {
            $lockedRequest = ServiceRequest::whereKey($serviceRequest)->lockForUpdate()->firstOrFail();
            abort_unless($lockedRequest->service_finder_id === $serviceFinder->id && in_array($lockedRequest->status, [ServiceRequestStatus::Requested, ServiceRequestStatus::Searching], true), 409);
            $lockedRequest->update(['status' => ServiceRequestStatus::Cancelled]);

            return $lockedRequest;
        });
    }
}
