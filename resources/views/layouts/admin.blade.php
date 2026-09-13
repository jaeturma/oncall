@props(['title' => 'Administration', 'description' => null, 'heading' => true])

@php
    $sections = [
        ['label' => 'Overview', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard'],
        ['label' => 'Users', 'route' => 'admin.users.index', 'match' => 'admin.users.*'],
        ['label' => 'Providers', 'route' => 'admin.providers.index', 'match' => 'admin.providers.*'],
        ['label' => 'Verifications', 'route' => 'admin.verifications.index', 'match' => 'admin.verifications.*'],
        ['label' => 'Jobs', 'route' => 'admin.jobs.index', 'match' => 'admin.jobs.*'],
        ['label' => 'Reports', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*'],
        ['label' => 'Enforcement', 'route' => 'admin.enforcement.index', 'match' => 'admin.enforcement.*'],
        ['label' => 'Disputes', 'route' => 'admin.disputes.index', 'match' => 'admin.disputes.*'],
        ['label' => 'Finance', 'route' => 'admin.finance.index', 'match' => 'admin.finance.*'],
        ['label' => 'Commissions', 'route' => 'admin.commissions.index', 'match' => 'admin.commissions.*'],
        ['label' => 'Account types', 'route' => 'admin.account-types.index', 'match' => 'admin.account-types.*'],
        ['label' => 'Catalog', 'route' => 'admin.catalog', 'match' => 'admin.catalog'],
        ['label' => 'SMS settings', 'route' => 'admin.settings.sms.edit', 'match' => 'admin.settings.sms.*'],
        ['label' => 'SMS logs', 'route' => 'admin.sms-logs.index', 'match' => 'admin.sms-logs.*'],
        ['label' => 'Audit log', 'route' => 'admin.audit-logs.index', 'match' => 'admin.audit-logs.*'],
    ];
@endphp

<x-layouts.app :title="$title" :description="$description" :heading="$heading" eyebrow="Administration" wide>
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset
    <nav class="-mx-4 mb-6 flex gap-1 overflow-x-auto border-b border-line px-4 pb-px scrollbar-none sm:mx-0 sm:px-0" aria-label="Administration sections">
        @foreach($sections as $section)
            @php($active = request()->routeIs($section['match']))
            <a class="-mb-px shrink-0 border-b-2 px-3 py-2.5 text-sm font-medium whitespace-nowrap {{ $active ? 'border-gold-500 text-navy-900' : 'border-transparent text-ink-muted hover:border-line-strong hover:text-ink' }}" href="{{ route($section['route']) }}" @if($active) aria-current="page" @endif>{{ $section['label'] }}</a>
        @endforeach
    </nav>
    {{ $slot }}
</x-layouts.app>
