<x-layouts.auth title="Choose a new password" heading="Choose a new password" intro="Pick something you don't use anywhere else. At least 8 characters.">
    <form class="card card-pad grid gap-5 sm:p-8" method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <x-form.field name="email" label="Email address" for="email_display">
            <input id="email_display" class="input" type="email" value="{{ $email }}" disabled>
        </x-form.field>
        <x-form.field name="password" label="New password" for="password" required>
            <input id="password" class="input {{ $errors->has('password') ? 'input-invalid' : '' }}" type="password" name="password" autocomplete="new-password" minlength="8" required aria-required="true" autofocus>
        </x-form.field>
        <x-form.field name="password_confirmation" label="Confirm new password" for="password_confirmation" required>
            <input id="password_confirmation" class="input" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required aria-required="true">
        </x-form.field>
        <x-ui.button variant="dark" size="lg" block data-loading-text="Saving…">Update password</x-ui.button>
    </form>
</x-layouts.auth>
