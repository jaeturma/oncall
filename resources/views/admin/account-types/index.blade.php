<x-layouts.admin title="Account types">
    <div class="grid gap-6">
        <x-flash />
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><h1 class="text-3xl font-black">Account types</h1><p class="mt-2 text-slate-600">Registration fee and sponsor commission rules are configured here, not in code.</p></div>
            <a class="rounded-lg bg-gold-400 px-4 py-2 font-bold text-navy-900 hover:bg-gold-500" href="{{ route('admin.account-types.create') }}">New account type</a>
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-6 py-3">Name</th><th class="px-6 py-3">Reg. fee</th><th class="px-6 py-3">Sponsor commission</th><th class="px-6 py-3">Users</th><th class="px-6 py-3">Active</th><th class="px-6 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($accountTypes as $accountType)
                            <tr>
                                <td class="px-6 py-4 font-semibold">{{ $accountType->name }}</td>
                                <td class="px-6 py-4">PHP {{ number_format((float) $accountType->registration_fee, 2) }}</td>
                                <td class="px-6 py-4">
                                    @php($type = $accountType->sponsor_commission_type)
                                    {{ $type === App\Enums\CommissionType::None ? 'None' : ($type === App\Enums\CommissionType::Fixed ? 'PHP '.number_format((float) $accountType->sponsor_commission_value, 2).' fixed' : number_format((float) $accountType->sponsor_commission_value, 2).'% of fee') }}
                                </td>
                                <td class="px-6 py-4">{{ $accountType->users_count }}</td>
                                <td class="px-6 py-4">
                                    <form method="POST" action="{{ route('admin.account-types.toggle', $accountType) }}">@csrf @method('PATCH')
                                        <button class="rounded-full px-3 py-1 text-xs font-bold {{ $accountType->active ? 'bg-gold-100 text-navy-900' : 'bg-slate-200 text-slate-600' }}">{{ $accountType->active ? 'Active' : 'Inactive' }}</button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-right"><a class="font-bold text-navy-800 hover:underline" href="{{ route('admin.account-types.edit', $accountType) }}">Edit</a></td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-8 text-center text-slate-500" colspan="6">No account types configured. New registrations will have none until you add one.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
