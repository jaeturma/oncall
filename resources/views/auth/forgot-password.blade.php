<x-layouts.auth title="Reset password" heading="Reset your password" intro="Enter the email address on your account and we'll send you a link to choose a new password.">
    <form class="card card-pad grid gap-5 sm:p-8" method="POST" action="{{ route('password.email') }}">
        @csrf
        <x-form.input name="email" type="email" label="Email address" autocomplete="email" inputmode="email" placeholder="you@example.com" required autofocus />
        <x-ui.button variant="dark" size="lg" block data-loading-text="Sending…">Email me a reset link</x-ui.button>
    </form>
    <p class="mt-6 text-center text-sm text-ink-secondary">Remembered it? <a class="font-semibold text-navy-800 hover:underline" href="{{ route('login') }}">Back to sign in</a></p>
</x-layouts.auth>
