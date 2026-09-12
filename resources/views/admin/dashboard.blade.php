<x-layouts.admin title="Administration overview" description="Live counts from the platform. Click any card to open its queue.">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Pending verifications" :value="$metrics['pending_verifications']" icon="identification" tone="warning" :href="route('admin.verifications.index')" />
        <x-ui.stat-card label="Active jobs" :value="$metrics['active_jobs']" icon="briefcase" tone="brand" :href="route('admin.jobs.index')" />
        <x-ui.stat-card label="Open reports" :value="$metrics['open_reports']" icon="flag" tone="danger" :href="route('admin.reports.index')" />
        <x-ui.stat-card label="Enforcement cases" :value="$metrics['open_enforcement']" icon="scale" tone="danger" :href="route('admin.enforcement.index')" />
        <x-ui.stat-card label="Users" :value="$metrics['users']" icon="users" :href="route('admin.users.index')" />
        <x-ui.stat-card label="Providers" :value="$metrics['providers']" icon="user" :href="route('admin.providers.index')" />
        <x-ui.stat-card label="Active services" :value="$metrics['services']" icon="squares" :href="route('admin.catalog')" />
        <x-ui.stat-card label="Audit events" :value="$metrics['audit_events']" icon="clipboard" :href="route('admin.audit-logs.index')" />
    </div>

    <section class="mt-8">
        <h2 class="h3">Finance, disputes &amp; sponsorship</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach([
                ['route' => 'admin.finance.index', 'icon' => 'chart', 'title' => 'Finance reports', 'text' => 'Reconciliation, revenue, pipelines, ledger movement, CSV export.'],
                ['route' => 'admin.disputes.index', 'icon' => 'scale', 'title' => 'Disputes', 'text' => 'Resolve job disputes; payments stay frozen until you do.'],
                ['route' => 'admin.account-types.index', 'icon' => 'cog', 'title' => 'Account types', 'text' => 'Registration fees, sponsor and platform commission rules.'],
                ['route' => 'admin.commissions.index', 'icon' => 'banknotes', 'title' => 'Sponsor commissions', 'text' => 'Approve or reverse posted commissions.'],
                ['route' => 'staff.withdrawals.index', 'icon' => 'arrow-path', 'title' => 'Withdrawal queue', 'text' => 'Accounting, Budget, and Cashier disbursement steps.'],
                ['route' => 'staff.job-payments.index', 'icon' => 'document-check', 'title' => 'Job payments', 'text' => 'Release completed-job earnings into provider wallets.'],
            ] as $link)
                <a class="card card-interactive flex items-start gap-4 p-5" href="{{ route($link['route']) }}">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-navy-50 text-navy-800"><x-ui.icon :name="$link['icon']" class="size-5" /></span>
                    <span class="min-w-0"><span class="block font-semibold text-ink">{{ $link['title'] }}</span><span class="mt-0.5 block text-sm text-ink-secondary">{{ $link['text'] }}</span></span>
                </a>
            @endforeach
        </div>
    </section>
</x-layouts.admin>
