<x-layouts.admin title="Account types" description="Registration fee and sponsor commission rules are configured here, not in code.">
    <x-slot:actions><x-ui.button :href="route('admin.account-types.create')" variant="primary" icon="plus">New account type</x-ui.button></x-slot:actions>

    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Name</th><th class="num">Registration fee</th><th>Sponsor commission</th><th>Platform commission</th><th class="num">Users</th><th>Active</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse($accountTypes as $accountType)
                        @php($type = $accountType->sponsor_commission_type)
                        <tr>
                            <td class="font-semibold whitespace-nowrap">{{ $accountType->name }}<span class="block text-xs font-normal text-ink-muted">{{ $accountType->slug }}</span></td>
                            <td class="num whitespace-nowrap">₱{{ number_format((float) $accountType->registration_fee, 2) }}</td>
                            <td class="whitespace-nowrap">{{ $type === App\Enums\CommissionType::None ? 'None' : ($type === App\Enums\CommissionType::Fixed ? '₱'.number_format((float) $accountType->sponsor_commission_value, 2).' fixed' : number_format((float) $accountType->sponsor_commission_value, 2).'% of fee') }}</td>
                            <td class="whitespace-nowrap">{{ $accountType->platform_commission_percent !== null ? number_format((float) $accountType->platform_commission_percent, 2).'%' : 'Default ('.config('oncall.platform.commission_percent').'%)' }}</td>
                            <td class="num">{{ $accountType->users_count }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.account-types.toggle', $accountType) }}" data-skip-loading>@csrf @method('PATCH')
                                    <button type="submit" class="badge {{ $accountType->active ? 'badge-success' : 'badge-neutral' }} cursor-pointer hover:ring-2 hover:ring-navy-200" title="Click to {{ $accountType->active ? 'deactivate' : 'activate' }}">{{ $accountType->active ? 'Active' : 'Inactive' }}</button>
                                </form>
                            </td>
                            <td class="text-right whitespace-nowrap"><a class="text-sm font-semibold text-navy-800 hover:underline" href="{{ route('admin.account-types.edit', $accountType) }}">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-ink-secondary">No account types configured. New registrations will have none until you add one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
