@php
    $preselectedRole = old('role', request('role') === 'provider' ? 'SERVICE_PROVIDER' : 'SERVICE_FINDER');
    $steps = ['Account', 'Your role'];
@endphp

<x-layouts.auth title="Create account" heading="Create your Oncall account" intro="Only the basics for now. Identity verification comes later, as a guided step, so you can look around first." wide>
    <ol class="mb-6 flex items-center gap-3 text-sm" aria-label="Registration steps">
        @foreach($steps as $index => $step)
            <li class="group flex items-center gap-2 data-[state=todo]:text-ink-muted" data-step-indicator data-state="{{ $index === 0 ? 'current' : 'todo' }}">
                <span class="flex size-7 items-center justify-center rounded-full text-xs font-bold ring-1 ring-line group-data-[state=current]:bg-navy-900 group-data-[state=current]:text-white group-data-[state=current]:ring-navy-900 group-data-[state=done]:bg-success-600 group-data-[state=done]:text-white group-data-[state=done]:ring-success-600">{{ $index + 1 }}</span>
                <span class="font-medium">{{ $step }}</span>
            </li>
            @if(! $loop->last)<li class="h-px flex-1 bg-line" aria-hidden="true"></li>@endif
        @endforeach
    </ol>

    <form class="card card-pad grid gap-6 sm:p-8" method="POST" action="{{ route('register') }}" data-stepper novalidate>
        @csrf

        {{-- Step 1: account --}}
        <fieldset class="grid gap-5" data-step>
            <legend class="sr-only">Account details</legend>
            <h2 class="h3 outline-none" data-step-title>Account details</h2>
            <x-form.input name="name" label="Full name" autocomplete="name" placeholder="e.g. Maria Dela Cruz" required />
            <x-form.input name="email" type="email" label="Email address" autocomplete="email" inputmode="email" placeholder="you@example.com" required />
            <x-form.input name="phone" type="tel" label="Mobile number" autocomplete="tel" inputmode="tel" placeholder="09XX XXX XXXX" optional hint="Used for account recovery and, later, mobile verification." />
            <div class="grid gap-5 sm:grid-cols-2 sm:items-start">
                <x-form.field name="password" label="Password" for="password" required hint="At least 8 characters.">
                    <input id="password" class="input {{ $errors->has('password') ? 'input-invalid' : '' }}" type="password" name="password" autocomplete="new-password" minlength="8" required aria-required="true">
                </x-form.field>
                <x-form.field name="password_confirmation" label="Confirm password" for="password_confirmation" required>
                    <input id="password_confirmation" class="input" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required aria-required="true">
                </x-form.field>
            </div>
            <div class="flex justify-end">
                <x-ui.button type="button" variant="dark" size="lg" icon-right="arrow-right" data-step-next>Continue</x-ui.button>
            </div>
        </fieldset>

        {{-- Step 2: role & sponsor --}}
        <fieldset class="grid gap-5" data-step hidden>
            <legend class="sr-only">Your role</legend>
            <h2 class="h3 outline-none" data-step-title>How will you use Oncall?</h2>
            <div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Account type">
                <label class="choice">
                    <input class="radio mt-0.5" type="radio" name="role" value="SERVICE_FINDER" @checked($preselectedRole === 'SERVICE_FINDER') required>
                    <span class="min-w-0">
                        <span class="flex items-center gap-2 text-sm font-semibold text-ink"><x-ui.icon name="search" class="size-4 text-navy-700" />I need help</span>
                        <span class="mt-1 block text-sm text-ink-secondary">Find and request verified providers for household, skilled, and professional services.</span>
                    </span>
                </label>
                <label class="choice">
                    <input class="radio mt-0.5" type="radio" name="role" value="SERVICE_PROVIDER" @checked($preselectedRole === 'SERVICE_PROVIDER') required>
                    <span class="min-w-0">
                        <span class="flex items-center gap-2 text-sm font-semibold text-ink"><x-ui.icon name="briefcase" class="size-4 text-navy-700" />I offer services</span>
                        <span class="mt-1 block text-sm text-ink-secondary">Create a provider profile, get verified, and receive requests from customers nearby.</span>
                    </span>
                </label>
            </div>
            @error('role')<p class="field-error"><x-ui.icon name="exclamation-triangle" class="mt-0.5 size-4" />{{ $message }}</p>@enderror

            <x-form.input name="sponsor_email" type="email" label="Sponsor or reference email" inputmode="email" placeholder="Email of the person who referred you" optional hint="If an existing Oncall member referred you, enter their email. Sponsorship is single level and can be reviewed by Oncall." />

            <x-ui.alert tone="accent" title="What happens after you sign up">
                To protect everyone on the platform, Oncall asks for a government ID before you can send or accept requests. Documents are stored privately and reviewed by staff, never shown publicly. You can do this from your dashboard whenever you're ready.
            </x-ui.alert>

            <p class="text-xs text-ink-muted">By creating an account you agree to keep communication, agreements, and payments on Oncall Philippines. Transactions arranged outside the platform cannot be monitored or supported.</p>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-ui.button type="button" variant="ghost" icon="arrow-left" data-step-prev>Back</x-ui.button>
                <x-ui.button variant="primary" size="lg" data-loading-text="Creating account…">Create account</x-ui.button>
            </div>
        </fieldset>
    </form>
    <p class="mt-6 text-center text-sm text-ink-secondary">Already have an account? <a class="font-semibold text-navy-800 hover:underline" href="{{ route('login') }}">Sign in</a></p>
</x-layouts.auth>
