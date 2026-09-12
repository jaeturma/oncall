<x-layouts.admin title="Users" description="Every account on the platform. Filter by role or standing.">
    <form class="card mb-5 grid gap-3 p-4 sm:grid-cols-[minmax(0,1fr)_12rem_12rem_auto] sm:items-end" method="GET" data-skip-loading>
        <x-form.field name="q" label="Search" for="q"><input id="q" class="input" name="q" value="{{ request('q') }}" placeholder="Name or email"></x-form.field>
        <x-form.field name="role" label="Role" for="role">
            <select id="role" class="select" name="role"><option value="">All roles</option>@foreach(App\Enums\UserRole::cases() as $role)<option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ str($role->value)->replace('_', ' ')->lower()->ucfirst() }}</option>@endforeach</select>
        </x-form.field>
        <x-form.field name="status" label="Standing" for="status">
            <select id="status" class="select" name="status"><option value="">All statuses</option>@foreach(App\Enums\UserStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ str($status->value)->replace('_', ' ')->lower()->ucfirst() }}</option>@endforeach</select>
        </x-form.field>
        <x-ui.button variant="dark" icon="funnel">Filter</x-ui.button>
    </form>

    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>User</th><th>Role</th><th>Standing</th><th>Identity</th><th>Joined</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td><div class="flex items-center gap-3"><x-ui.avatar :name="$user->name" size="sm" /><div class="min-w-0"><p class="font-semibold text-ink">{{ $user->name }}</p><p class="truncate text-xs text-ink-muted">{{ $user->email }}</p></div></div></td>
                            <td class="whitespace-nowrap">{{ str($user->role->value)->replace('_', ' ')->lower()->ucfirst() }}</td>
                            <td><x-ui.status-badge :status="$user->status" /></td>
                            <td><x-ui.status-badge :status="$user->identity_verification_status" /></td>
                            <td class="whitespace-nowrap text-ink-secondary">{{ $user->created_at->format('M j, Y') }}</td>
                            <td class="text-right whitespace-nowrap">@can('view-finance-reports')<a class="text-sm font-semibold text-navy-800 hover:underline" href="{{ route('admin.finance.statement', $user) }}">Statement</a>@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-ink-secondary">No users match those filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @if($users->hasPages())<div class="mt-5">{{ $users->links() }}</div>@endif
</x-layouts.admin>
