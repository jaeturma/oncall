@php
    $user = auth()->user();
    $unread = $user ? $user->unreadNotifications()->count() : 0;
    $unreadMessages = $user ? $user->unreadJobMessagesCount() : 0;
    $links = [
        ['label' => 'Find Services', 'href' => route('home').'#find-help', 'icon' => 'search'],
        ['label' => 'How It Works', 'href' => route('home').'#how-it-works', 'icon' => 'squares'],
        ['label' => 'Become a Provider', 'href' => route('register', ['role' => 'provider']), 'icon' => 'briefcase'],
        ['label' => 'Safety', 'href' => route('home').'#safety', 'icon' => 'shield-check'],
    ];
@endphp

<header class="sticky top-0 z-30 border-b border-line bg-surface/95 backdrop-blur supports-[backdrop-filter]:bg-surface/85">
    <div class="container-x flex h-16 items-center justify-between gap-4">
        <x-ui.logo />

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Primary navigation">
            @foreach($links as $link)
                <a class="nav-link" href="{{ $link['href'] }}">{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            @auth
                <a class="relative hidden size-11 items-center justify-center rounded-lg text-ink-secondary hover:bg-navy-50 hover:text-navy-900 sm:inline-flex" href="{{ route('messages.index') }}" aria-label="Messages{{ $unreadMessages > 0 ? ', '.$unreadMessages.' unread' : '' }}">
                    <x-ui.icon name="chat" class="size-5.5" />
                    @if($unreadMessages > 0)<span class="absolute top-1.5 right-1.5 min-w-4.5 rounded-full bg-gold-400 px-1 text-center text-[10px] font-bold leading-4 text-navy-900">{{ $unreadMessages > 99 ? '99+' : $unreadMessages }}</span>@endif
                </a>
                <a class="relative hidden size-11 items-center justify-center rounded-lg text-ink-secondary hover:bg-navy-50 hover:text-navy-900 sm:inline-flex" href="{{ route('notifications.index') }}" aria-label="Notifications{{ $unread > 0 ? ', '.$unread.' unread' : '' }}">
                    <x-ui.icon name="bell" class="size-5.5" />
                    @if($unread > 0)<span class="absolute top-1.5 right-1.5 min-w-4.5 rounded-full bg-gold-400 px-1 text-center text-[10px] font-bold leading-4 text-navy-900">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
                </a>
                <a class="btn btn-dark btn-sm hidden md:inline-flex" href="{{ route('dashboard') }}">Dashboard</a>
                <div class="relative hidden lg:block" data-menu>
                    <button type="button" class="flex items-center gap-2 rounded-full p-1 pr-2 hover:bg-navy-50" data-menu-button aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
                        <x-ui.avatar :initials="$user->initials" size="sm" />
                        <x-ui.icon name="chevron-down" class="size-4 text-ink-muted" />
                    </button>
                    <div class="menu" data-menu-panel hidden role="menu">
                        <div class="px-3 py-2">
                            <p class="truncate text-sm font-semibold text-ink">{{ $user->name }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ str($user->role?->value ?? 'SERVICE_FINDER')->replace('_', ' ')->lower()->ucfirst() }}</p>
                        </div>
                        <div class="my-1 divider"></div>
                        <a class="menu-item" role="menuitem" href="{{ route('dashboard') }}"><x-ui.icon name="squares" class="size-4 text-ink-muted" />Dashboard</a>
                        <a class="menu-item" role="menuitem" href="{{ route('messages.index') }}"><x-ui.icon name="chat" class="size-4 text-ink-muted" />Messages @if($unreadMessages > 0)<span class="ml-auto badge badge-accent">{{ $unreadMessages }}</span>@endif</a>
                        <a class="menu-item" role="menuitem" href="{{ route('notifications.index') }}"><x-ui.icon name="bell" class="size-4 text-ink-muted" />Notifications @if($unread > 0)<span class="ml-auto badge badge-accent">{{ $unread }}</span>@endif</a>
                        <a class="menu-item" role="menuitem" href="{{ route('wallet.index') }}"><x-ui.icon name="wallet" class="size-4 text-ink-muted" />Wallet</a>
                        <a class="menu-item" role="menuitem" href="{{ route('verification.index') }}"><x-ui.icon name="identification" class="size-4 text-ink-muted" />Verification</a>
                        <div class="my-1 divider"></div>
                        <form method="POST" action="{{ route('logout') }}" data-skip-loading>@csrf<button class="menu-item text-danger-700 hover:bg-danger-50" role="menuitem" type="submit"><x-ui.icon name="logout" class="size-4" />Sign out</button></form>
                    </div>
                </div>
            @else
                <a class="btn btn-ghost btn-sm hidden sm:inline-flex" href="{{ route('login') }}">Sign in</a>
                <a class="btn btn-primary btn-sm" href="{{ route('register') }}"><span class="hidden sm:inline">Create account</span><span class="sm:hidden">Get started</span></a>
            @endauth

            <button type="button" class="inline-flex size-11 items-center justify-center rounded-lg text-ink hover:bg-navy-50 lg:hidden" data-drawer-open="mobile-nav" aria-controls="mobile-nav" aria-expanded="false" aria-label="Open menu">
                <x-ui.icon name="bars-3" class="size-6" />
            </button>
        </div>
    </div>

    {{-- Mobile navigation drawer --}}
    <div id="mobile-nav" class="fixed inset-0 z-50 lg:hidden" data-drawer="mobile" hidden role="dialog" aria-modal="true" aria-label="Site menu">
        <div class="absolute inset-0 bg-navy-950/60" data-drawer-close></div>
        <div class="absolute inset-y-0 right-0 flex w-[min(22rem,90vw)] flex-col bg-surface shadow-pop">
            <div class="flex h-16 items-center justify-between border-b border-line px-4">
                <x-ui.logo />
                <button type="button" class="inline-flex size-11 items-center justify-center rounded-lg text-ink hover:bg-navy-50" data-drawer-close aria-label="Close menu"><x-ui.icon name="x-mark" class="size-6" /></button>
            </div>
            <nav class="flex-1 overflow-y-auto p-3" aria-label="Mobile navigation">
                @auth
                    <div class="mb-2 flex items-center gap-3 rounded-xl bg-navy-50 p-3">
                        <x-ui.avatar :initials="$user->initials" size="md" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">{{ $user->name }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ $user->email }}</p>
                        </div>
                    </div>
                    <a class="side-link" href="{{ route('dashboard') }}"><x-ui.icon name="squares" class="size-5 text-ink-muted" />Dashboard</a>
                    <a class="side-link" href="{{ route('messages.index') }}"><x-ui.icon name="chat" class="size-5 text-ink-muted" />Messages @if($unreadMessages > 0)<span class="ml-auto badge badge-accent">{{ $unreadMessages }}</span>@endif</a>
                    <a class="side-link" href="{{ route('notifications.index') }}"><x-ui.icon name="bell" class="size-5 text-ink-muted" />Notifications @if($unread > 0)<span class="ml-auto badge badge-accent">{{ $unread }}</span>@endif</a>
                    <div class="my-2 divider"></div>
                @endauth
                @foreach($links as $link)
                    <a class="side-link" href="{{ $link['href'] }}"><x-ui.icon :name="$link['icon']" class="size-5 text-ink-muted" />{{ $link['label'] }}</a>
                @endforeach
            </nav>
            <div class="grid gap-2 border-t border-line p-4">
                @auth
                    <form method="POST" action="{{ route('logout') }}" data-skip-loading>@csrf<button class="btn btn-secondary btn-block" type="submit"><x-ui.icon name="logout" class="size-4" />Sign out</button></form>
                @else
                    <a class="btn btn-primary btn-block" href="{{ route('register') }}">Create account</a>
                    <a class="btn btn-secondary btn-block" href="{{ route('login') }}">Sign in</a>
                @endauth
            </div>
        </div>
    </div>
</header>
