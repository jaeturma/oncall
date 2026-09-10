<?php

namespace App\Services;

use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\Job;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ServiceRequestService
{
    public function __construct(private readonly Notifier $notifier) {}

    public function create(User $serviceFinder, ProviderProfile $providerProfile, array $attributes): ServiceRequest
    {
        unset($attributes['safety_acknowledged']);

        $serviceRequest = $serviceFinder->serviceRequests()->create([
            ...$attributes,
            'requested_provider_id' => $providerProfile->user_id,
            'status' => ServiceRequestStatus::Requested,
            'safety_acknowledged_at' => now(),
        ]);

        $this->notifier->push(
            $providerProfile->user,
            'service_request.received',
            'New service request',
            $serviceFinder->name.' requested "'.$serviceRequest->title.'".',
            route('service-requests.show', $serviceRequest),
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

            $this->notifier->push(
                $lockedRequest->serviceFinder,
                'service_request.accepted',
                'Request accepted — booking confirmed',
                $provider->name.' accepted "'.$lockedRequest->title.'" at PHP '.$agreedPrice.'.',
                route('jobs.show', $job),
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

            $this->notifier->push(
                $lockedRequest->serviceFinder,
                'service_request.declined',
                'Provider declined your request',
                'Your request "'.$lockedRequest->title.'" is open again — you can request another provider.',
                route('service-requests.show', $lockedRequest),
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
