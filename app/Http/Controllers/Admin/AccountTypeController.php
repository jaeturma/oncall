<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountTypeRequest;
use App\Models\AccountType;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AccountTypeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', AccountType::class);

        return view('admin.account-types.index', [
            'accountTypes' => AccountType::query()->withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', AccountType::class);

        return view('admin.account-types.form', ['accountType' => new AccountType(['active' => true, 'requires_identity_verification' => true])]);
    }

    public function store(StoreAccountTypeRequest $request): RedirectResponse
    {
        $accountType = AccountType::create($request->attributesForModel());
        $this->audit($request, 'account_type.created', $accountType, null);

        return redirect()->route('admin.account-types.index')->with('status', 'Account type created.');
    }

    public function edit(AccountType $accountType): View
    {
        Gate::authorize('update', $accountType);

        return view('admin.account-types.form', ['accountType' => $accountType]);
    }

    public function update(StoreAccountTypeRequest $request, AccountType $accountType): RedirectResponse
    {
        $before = $accountType->toArray();
        $accountType->update($request->attributesForModel());
        $this->audit($request, 'account_type.updated', $accountType, $before);

        return redirect()->route('admin.account-types.index')->with('status', 'Account type updated.');
    }

    public function toggle(Request $request, AccountType $accountType): RedirectResponse
    {
        Gate::authorize('update', $accountType);
        $before = $accountType->toArray();
        $accountType->update(['active' => ! $accountType->active]);
        $this->audit($request, 'account_type.toggled', $accountType, $before);

        return back()->with('status', 'Account type '.($accountType->active ? 'activated' : 'deactivated').'.');
    }

    /**
     * @param  array<string, mixed>|null  $before
     */
    private function audit(Request $request, string $event, AccountType $accountType, ?array $before): void
    {
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'event' => $event,
            'subject_type' => AccountType::class,
            'subject_id' => $accountType->id,
            'before_json' => $before,
            'after_json' => $accountType->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
