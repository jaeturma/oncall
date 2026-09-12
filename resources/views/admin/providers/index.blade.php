<x-layouts.admin title="Providers" description="Provider profiles and their review status.">
    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Provider</th><th>Location</th><th>Availability</th><th>Services</th><th>Rating</th><th class="num">Completed</th><th>Profile review</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse($providers as $profile)
                        <tr>
                            <td><div class="flex items-center gap-3"><x-ui.avatar :name="$profile->user->name" size="sm" /><div class="min-w-0"><p class="font-semibold text-ink">{{ $profile->user->name }}</p><p class="truncate text-xs text-ink-muted">{{ $profile->user->email }}</p></div></div></td>
                            <td class="whitespace-nowrap text-ink-secondary">{{ $profile->municipality->name }}, {{ $profile->province->name }}</td>
                            <td><x-ui.availability-badge :status="$profile->availability_status" /></td>
                            <td class="whitespace-nowrap">{{ $profile->provider_services_count }}</td>
                            <td><x-ui.rating :value="$profile->rating_cached" /></td>
                            <td class="num">{{ $profile->completed_jobs_cached }}</td>
                            <td><x-ui.status-badge :status="$profile->verification_status" /></td>
                            <td class="text-right whitespace-nowrap">@if($profile->verification_status === App\Enums\VerificationStatus::Verified)<a class="text-sm font-semibold text-navy-800 hover:underline" href="{{ route('providers.show', $profile) }}">Public profile</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-ink-secondary">No providers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @if($providers->hasPages())<div class="mt-5">{{ $providers->links() }}</div>@endif
</x-layouts.admin>
