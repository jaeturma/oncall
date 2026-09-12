<?php

namespace App\Policies;

use App\Enums\RestrictedCapability;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canUseMarketplace();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return $serviceRequest->service_finder_id === $user->id || $serviceRequest->requested_provider_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::ServiceFinder
            && $user->status === UserStatus::Active
            && $user->canRequestService()
            && ! $user->isCapabilityRestricted(RestrictedCapability::RequestService)
            && ! $user->isCapabilityRestricted(RestrictedCapability::NewBookings);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceRequest $serviceRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceRequest $serviceRequest): bool
    {
        return false;
    }

    public function cancel(User $user, ServiceRequest $serviceRequest): bool
    {
        return $serviceRequest->service_finder_id === $user->id
            && in_array($serviceRequest->status, [ServiceRequestStatus::Requested, ServiceRequestStatus::Searching], true);
    }

    public function respond(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->role === UserRole::ServiceProvider
            && $serviceRequest->requested_provider_id === $user->id
            && $serviceRequest->status === ServiceRequestStatus::Requested
            && ! $user->isCapabilityRestricted(RestrictedCapability::AcceptJobs)
            && ! $user->isCapabilityRestricted(RestrictedCapability::NewBookings);
    }
}
