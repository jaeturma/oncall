@php
    use App\Enums\UserRole;

    $user = auth()->user();
    $role = $user->role ?? UserRole::ServiceFinder;
    $unread = $user->unreadNotifications()->count();
    $isStaff = in_array($role, [UserRole::Accounting, UserRole::Budget, UserRole::Cashier], true);
    $isAdmin = $role === UserRole::Admin;

    $items = [];
    $items[] = ['label' => 'Overview', 'href' => route('dashboard'), 'icon' => 'squares', 'active' => request()->routeIs('dashboard') || request()->routeIs('provider.dashboard') || request()->routeIs('admin.dashboard')];

    if ($role === UserRole::ServiceFinder) {
        $items[] = ['label' => 'Find services', 'href' => route('home').'#find-help', 'icon' => 'search', 'active' => false];
    }
    if ($user->can('viewAny', App\Models\ServiceRequest::class)) {
        $items[] = ['label' => $role === UserRole::ServiceProvider ? 'Incoming requests' : 'My requests', 'href' => route('service-requests.index'), 'icon' => 'inbox', 'active' => request()->routeIs('service-requests.*')];
    }
    if ($user->can('viewAny', App\Models\Job::class)) {
        $items[] = ['label' => 'Bookings & jobs', 'href' => route('jobs.index'), 'icon' => 'briefcase', 'active' => request()->routeIs('jobs.*')];
        $items[] = ['label' => 'Messages', 'href' => route('messages.index'), 'icon' => 'chat', 'active' => request()->routeIs('messages.*'), 'count' => $user->unreadJobMessagesCount()];
    }
    $items[] = ['label' => 'Notifications', 'href' => route('notifications.index'), 'icon' => 'bell', 'active' => request()->routeIs('notifications.*'), 'count' => $unread];

    if ($role === UserRole::ServiceProvider && $user->providerProfile) {
        $items[] = ['label' => 'Provider profile', 'href' => route('provider.profiles.edit', $user->providerProfile), 'icon' => 'user', 'active' => request()->routeIs('provider.profiles.*')];
    }
    if (! $isAdmin) {
        $items[] = ['label' => 'Verification', 'href' => route('verification.index'), 'icon' => 'identification', 'active' => request()->routeIs('verification.*')];
    }
    if (! $isStaff && ! $isAdmin) {
        $items[] = ['label' => 'Wallet', 'href' => route('wallet.index'), 'icon' => 'wallet', 'active' => request()->routeIs('wallet.*') || request()->routeIs('withdrawals.*')];
        $items[] = ['label' => 'Sponsored users', 'href' => route('sponsor.referrals'), 'icon' => 'users', 'active' => request()->routeIs('sponsor.*')];
    }
    if ($user->can('viewQueue', App\Models\Withdrawal::class)) {
        $items[] = ['label' => 'Withdrawal queue', 'href' => route('staff.withdrawals.index'), 'icon' => 'banknotes', 'active' => request()->routeIs('staff.withdrawals.*')];
    }
    if ($user->can('viewQueue', App\Models\JobPayment::class)) {
        $items[] = ['label' => 'Job payments', 'href' => route('staff.job-payments.index'), 'icon' => 'document-check', 'active' => request()->routeIs('staff.job-payments.*')];
    }
    $items[] = ['label' => 'Safety cases', 'href' => route('enforcement-cases.index'), 'icon' => 'flag', 'active' => request()->routeIs('enforcement-cases.*')];
    if ($isAdmin) {
        $items[] = ['label' => 'Administration', 'href' => route('admin.dashboard'), 'icon' => 'cog', 'active' => request()->routeIs('admin.*') || request()->routeIs('staff.*')];
    }
@endphp

<aside class="min-w-0 lg:sticky lg:top-20 lg:self-start">
    <div class="hidden items-center gap-3 rounded-2xl bg-navy-900 p-4 text-white lg:flex">
        <x-ui.avatar :initials="$user->initials" size="md" tone="dark" />
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
            <p class="truncate text-xs text-gold-300">{{ str($role->value)->replace('_', ' ')->lower()->ucfirst() }}</p>
        </div>
    </div>
    <nav class="flex w-full gap-1 overflow-x-auto pb-1 scrollbar-none lg:mt-3 lg:flex-col lg:overflow-visible lg:pb-0" aria-label="Account navigation">
        @foreach($items as $item)
            <a class="side-link shrink-0 whitespace-nowrap {{ $item['active'] ? 'side-link-active' : 'bg-surface ring-1 ring-line lg:bg-transparent lg:ring-0' }}" href="{{ $item['href'] }}" @if($item['active']) aria-current="page" @endif>
                <x-ui.icon :name="$item['icon']" class="size-5 {{ $item['active'] ? 'text-gold-300' : 'text-ink-muted' }}" />
                {{ $item['label'] }}
                @if(($item['count'] ?? 0) > 0)<span class="ml-auto rounded-full bg-gold-400 px-2 py-0.5 text-[11px] font-bold text-navy-900">{{ $item['count'] }}</span>@endif
            </a>
        @endforeach
    </nav>
</aside>
