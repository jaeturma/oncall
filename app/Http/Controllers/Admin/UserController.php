<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canAccessAdmin(), 403);
        $role = UserRole::tryFrom($request->string('role')->value());
        $status = UserStatus::tryFrom($request->string('status')->value());
        $search = $request->string('q')->trim()->value();
        $users = User::query()->with('providerProfile:id,user_id,verification_status')->when($role, fn ($query) => $query->where('role', $role))->when($status, fn ($query) => $query->where('status', $status))->when($search, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))->latest('id')->paginate(20)->withQueryString();

        return view('admin.users.index', ['users' => $users]);
    }
}
