<x-layouts.public :title="$title ?? 'Dashboard'">
    <div class="border-b border-slate-200 bg-white px-6 py-8">
        <div class="mx-auto max-w-7xl">
            <p class="text-sm font-semibold text-navy-800">Home / Dashboard</p>
            <h1 class="mt-1 text-3xl font-black text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
        </div>
    </div>
    <div class="mx-auto grid max-w-7xl gap-8 px-6 py-10 md:grid-cols-[16rem_1fr]">
        <aside class="self-start overflow-hidden rounded-2xl bg-navy-900 text-white shadow-lg">
            <div class="border-b border-white/10 p-6 text-center">
                <span class="mx-auto flex size-20 items-center justify-center rounded-full border-4 border-white/15 bg-white/10 text-2xl font-black uppercase tracking-wide text-gold-300" aria-hidden="true">{{ auth()->user()->initials }}</span>
                <p class="mt-3 font-bold">{{ auth()->user()->name }}</p>
                <p class="text-xs uppercase tracking-widest text-gold-300">Oncall dashboard</p>
            </div>
            <nav class="grid p-3" aria-label="Dashboard navigation">
                @php($unreadNotifications = auth()->user()->unreadNotifications()->count())
                <a class="flex items-center justify-between rounded-lg px-4 py-3 font-semibold hover:bg-white/10" href="{{ route('notifications.index') }}">
                    Notifications
                    @if($unreadNotifications > 0)<span class="rounded-full bg-gold-400 px-2 py-0.5 text-xs font-black text-navy-900">{{ $unreadNotifications }}</span>@endif
                </a>
                <a class="rounded-lg bg-gold-400 px-4 py-3 font-semibold text-navy-900" href="{{ route('provider.dashboard') }}">Provider dashboard</a>
                @can('viewAny', App\Models\ServiceRequest::class)
                    <a class="rounded-lg px-4 py-3 font-semibold hover:bg-white/10" href="{{ route('service-requests.index') }}">Service requests</a>
                @endcan
                @can('viewAny', App\Models\Job::class)
                    <a class="rounded-lg px-4 py-3 font-semibold hover:bg-white/10" href="{{ route('jobs.index') }}">Bookings and jobs</a>
                @endcan
                <a class="rounded-lg px-4 py-3 font-semibold hover:bg-white/10" href="{{ route('enforcement-cases.index') }}">Safety cases</a>
                <a class="rounded-lg px-4 py-3 font-semibold hover:bg-white/10" href="{{ route('wallet.index') }}">Wallet</a>
                <a class="rounded-lg px-4 py-3 font-semibold hover:bg-white/10" href="{{ route('sponsor.referrals') }}">Sponsored users</a>
                @can('viewQueue', App\Models\Withdrawal::class)
                    <a class="rounded-lg px-4 py-3 font-semibold hover:bg-white/10" href="{{ route('staff.withdrawals.index') }}">Withdrawal queue</a>
                @endcan
                @can('viewQueue', App\Models\JobPayment::class)
                    <a class="rounded-lg px-4 py-3 font-semibold hover:bg-white/10" href="{{ route('staff.job-payments.index') }}">Job payments</a>
                @endcan
                @if(auth()->user()->role === App\Enums\UserRole::Admin)<a class="rounded-lg px-4 py-3 font-semibold hover:bg-white/10" href="{{ route('admin.dashboard') }}">Administration</a>@endif
                <a class="rounded-lg px-4 py-3 font-semibold hover:bg-white/10" href="{{ route('verification.index') }}">Identity verification</a>
                @if(auth()->user()->providerProfile)
                    <a class="rounded-lg px-4 py-3 font-semibold hover:bg-white/10" href="{{ route('provider.profiles.edit', auth()->user()->providerProfile) }}">Edit provider profile</a>
                @endif
            </nav>
        </aside>
        <section>{{ $slot }}</section>
    </div>
</x-layouts.public>
