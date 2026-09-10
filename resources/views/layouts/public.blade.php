<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Oncall Philippines' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4" aria-label="Primary navigation">
            <a href="{{ route('home') }}" class="text-xl font-black text-navy-800">Oncall Philippines</a>
            <div class="flex items-center gap-3">
                <a href="{{ route('providers.search') }}" class="hidden font-semibold text-navy-800 hover:underline sm:inline">Find help</a>
                @auth
                    @php($unread = auth()->user()->unreadNotifications()->count())
                    <a href="{{ route('notifications.index') }}" class="relative font-semibold text-navy-800 hover:underline">
                        Notifications
                        @if($unread > 0)<span class="ml-1 rounded-full bg-gold-400 px-2 py-0.5 text-xs font-black text-navy-900">{{ $unread }}</span>@endif
                    </a>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-lg bg-gold-400 px-4 py-2 font-semibold text-navy-900 hover:bg-gold-500">
                        <span class="flex size-6 items-center justify-center rounded-full bg-navy-900 text-xs font-black text-gold-300" aria-hidden="true">{{ auth()->user()->initials }}</span>
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="font-semibold text-navy-800 hover:underline">Sign in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-gold-400 px-4 py-2 font-semibold text-navy-900 hover:bg-gold-500">Create account</a>
                @endauth
            </div>
        </nav>
    </header>
    <main class="flex-1">{{ $slot }}</main>
    <footer class="mt-16 border-t border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-3 px-6 py-10 text-sm text-slate-600">
            <p class="font-black text-navy-800">Oncall Philippines</p>
            <p>Keep communication, agreements, and payments on the platform so Oncall can help if something goes wrong.</p>
            <p><strong class="text-red-800">Not an emergency service.</strong> For any life-threatening emergency, call 911 or your local hotline.</p>
            <p class="mt-2 text-slate-500">&copy; {{ now()->year }} Oncall Philippines. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
