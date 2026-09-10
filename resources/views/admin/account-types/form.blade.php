<x-layouts.admin :title="$accountType->exists ? 'Edit account type' : 'New account type'">
    <div class="mx-auto max-w-2xl rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h1 class="text-3xl font-black">{{ $accountType->exists ? 'Edit account type' : 'New account type' }}</h1>

        @if($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><ul class="list-disc space-y-1 pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form class="mt-8 grid gap-6" method="POST" action="{{ $accountType->exists ? route('admin.account-types.update', $accountType) : route('admin.account-types.store') }}">
            @csrf
            @if($accountType->exists) @method('PUT') @endif

            <label class="grid gap-2 font-semibold">Name
                <input class="rounded-lg border border-slate-300 p-3" type="text" name="name" value="{{ old('name', $accountType->name) }}" maxlength="120" required>
            </label>

            <label class="grid gap-2 font-semibold">Registration fee (PHP)
                <input class="rounded-lg border border-slate-300 p-3" type="number" name="registration_fee" min="0" step="0.01" value="{{ old('registration_fee', $accountType->registration_fee ?? '0.00') }}" required>
                <span class="text-sm font-normal text-slate-500">Percentage commissions are calculated against this amount.</span>
            </label>

            <div class="grid gap-6 sm:grid-cols-2">
                <label class="grid gap-2 font-semibold">Sponsor commission type
                    <select class="rounded-lg border border-slate-300 p-3" name="sponsor_commission_type">
                        @foreach(App\Enums\CommissionType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('sponsor_commission_type', $accountType->sponsor_commission_type?->value ?? 'NONE') === $type->value)>{{ str($type->value)->title() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-2 font-semibold">Commission value
                    <input class="rounded-lg border border-slate-300 p-3" type="number" name="sponsor_commission_value" min="0" step="0.01" value="{{ old('sponsor_commission_value', $accountType->sponsor_commission_value ?? '0.00') }}" required>
                    <span class="text-sm font-normal text-slate-500">Peso amount for Fixed, or percent (0–100) for Percentage.</span>
                </label>
            </div>

            <label class="flex items-start gap-3 font-semibold"><input class="mt-1" type="checkbox" name="requires_identity_verification" value="1" @checked(old('requires_identity_verification', $accountType->requires_identity_verification ?? true))> Requires identity verification</label>
            <label class="flex items-start gap-3 font-semibold"><input class="mt-1" type="checkbox" name="active" value="1" @checked(old('active', $accountType->active ?? true))> Active (available to new registrations)</label>

            <div class="flex gap-3">
                <button class="rounded-lg bg-gold-400 px-6 py-3 font-bold text-navy-900 hover:bg-gold-500">Save</button>
                <a class="rounded-lg border border-slate-300 px-6 py-3 font-bold" href="{{ route('admin.account-types.index') }}">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
