<x-layouts.auth title="Sign in" heading="Sign in to Oncall" intro="Welcome back. Sign in to request services, manage bookings, and keep everything on record.">
    <form class="card card-pad grid gap-5 sm:p-8" method="POST" action="{{ route('login') }}">
        @csrf
        <x-form.input name="email" type="email" label="Email address" autocomplete="email" inputmode="email" placeholder="you@example.com" required autofocus />
        <x-form.field name="password" label="Password" for="password" required>
            <input id="password" class="input {{ $errors->has('password') ? 'input-invalid' : '' }}" type="password" name="password" autocomplete="current-password" required aria-required="true">
        </x-form.field>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm text-ink-secondary"><input class="checkbox" type="checkbox" name="remember" value="1">Keep me signed in</label>
            <a class="text-sm font-medium text-navy-800 hover:underline" href="{{ route('password.request') }}">Forgot password?</a>
        </div>
        <x-ui.button variant="dark" size="lg" block data-loading-text="Signing in…">Sign in</x-ui.button>
    </form>
    <p class="mt-6 text-center text-sm text-ink-secondary">New to Oncall? <a class="font-semibold text-navy-800 hover:underline" href="{{ route('register') }}">Create a free account</a></p>
    <x-emergency-notice variant="compact" class="mt-8 justify-center" />
</x-layouts.auth>
