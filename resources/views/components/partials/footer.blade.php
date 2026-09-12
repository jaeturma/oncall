<footer class="mt-auto border-t border-line bg-surface">
    <div class="container-x grid gap-10 py-12 md:grid-cols-[1.4fr_1fr_1fr]">
        <div class="max-w-sm">
            <x-ui.logo />
            <p class="mt-4 text-sm text-ink-secondary">A trusted Philippine service network. Find verified local help, keep every agreement recorded on Oncall, and stay protected from start to finish.</p>
            <x-emergency-notice variant="compact" class="mt-5" />
        </div>
        <nav aria-label="Footer: product">
            <p class="text-sm font-semibold text-ink">Oncall</p>
            <ul class="mt-3 grid gap-2 text-sm text-ink-secondary">
                <li><a class="hover:text-navy-900 hover:underline" href="{{ route('home') }}#find-help">Find services</a></li>
                <li><a class="hover:text-navy-900 hover:underline" href="{{ route('home') }}#how-it-works">How it works</a></li>
                <li><a class="hover:text-navy-900 hover:underline" href="{{ route('register', ['role' => 'provider']) }}">Become a provider</a></li>
                <li><a class="hover:text-navy-900 hover:underline" href="{{ route('home') }}#safety">Safety on Oncall</a></li>
            </ul>
        </nav>
        <nav aria-label="Footer: account">
            <p class="text-sm font-semibold text-ink">Your account</p>
            <ul class="mt-3 grid gap-2 text-sm text-ink-secondary">
                @auth
                    <li><a class="hover:text-navy-900 hover:underline" href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li><a class="hover:text-navy-900 hover:underline" href="{{ route('notifications.index') }}">Notifications</a></li>
                    <li><a class="hover:text-navy-900 hover:underline" href="{{ route('verification.index') }}">Identity verification</a></li>
                    <li><a class="hover:text-navy-900 hover:underline" href="{{ route('wallet.index') }}">Wallet</a></li>
                @else
                    <li><a class="hover:text-navy-900 hover:underline" href="{{ route('login') }}">Sign in</a></li>
                    <li><a class="hover:text-navy-900 hover:underline" href="{{ route('register') }}">Create an account</a></li>
                    <li><a class="hover:text-navy-900 hover:underline" href="{{ route('password.request') }}">Forgot password</a></li>
                @endauth
            </ul>
        </nav>
    </div>
    <div class="border-t border-line">
        <div class="container-x flex flex-col gap-2 py-5 text-xs text-ink-muted sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} Oncall Philippines. All rights reserved.</p>
            <p>Keep communication, agreements, and payments on Oncall so we can help if something goes wrong.</p>
        </div>
    </div>
</footer>
