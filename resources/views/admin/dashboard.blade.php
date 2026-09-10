<x-layouts.admin title="Administration dashboard">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach(['users' => ['Users', 'admin.users.index'], 'providers' => ['Providers', 'admin.providers.index'], 'services' => ['Active services', 'admin.catalog'], 'active_jobs' => ['Active jobs', 'admin.jobs.index'], 'pending_verifications' => ['Pending verification', 'admin.verifications.index'], 'open_reports' => ['Open reports', 'admin.reports.index'], 'open_enforcement' => ['Enforcement cases', 'admin.enforcement.index'], 'audit_events' => ['Audit events', 'admin.audit-logs.index']] as $key => [$label, $route])
            <a class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:ring-gold-400" href="{{ route($route) }}"><p class="text-sm font-bold uppercase tracking-widest text-navy-800">{{ $label }}</p><p class="mt-3 text-4xl font-black">{{ $metrics[$key] }}</p></a>
        @endforeach
    </div>
    <section class="mt-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-xl font-black">Finance &amp; sponsorship</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <a class="rounded-xl bg-slate-50 p-4 hover:bg-gold-50" href="{{ route('admin.account-types.index') }}"><p class="font-bold">Account types</p><p class="mt-1 text-sm text-slate-600">Registration fees &amp; sponsor commission rules.</p></a>
            <a class="rounded-xl bg-slate-50 p-4 hover:bg-gold-50" href="{{ route('admin.commissions.index') }}"><p class="font-bold">Sponsor commissions</p><p class="mt-1 text-sm text-slate-600">Approve or reverse posted commissions.</p></a>
            <a class="rounded-xl bg-slate-50 p-4 hover:bg-gold-50" href="{{ route('staff.withdrawals.index') }}"><p class="font-bold">Withdrawal queue</p><p class="mt-1 text-sm text-slate-600">Accounting &rarr; Budget &rarr; Cashier disbursement.</p></a>
            <a class="rounded-xl bg-slate-50 p-4 hover:bg-gold-50" href="{{ route('staff.job-payments.index') }}"><p class="font-bold">Job payments</p><p class="mt-1 text-sm text-slate-600">Release completed-job earnings into provider wallets.</p></a>
        </div>
    </section>
</x-layouts.admin>
