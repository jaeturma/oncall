@php
    $placeholders = $event['placeholders'];
@endphp

<x-layouts.admin :title="'Edit template — '.$eventKey" description="Leave a field blank to use the safe application default shown as its placeholder.">
    <form class="card card-pad grid max-w-3xl gap-6 sm:p-8" method="POST" action="{{ route('admin.settings.notification-templates.update', $eventKey) }}">
        @csrf @method('PATCH')
        <x-form.errors />

        <p class="text-sm text-ink-secondary">
            Allowed placeholders:
            @if($placeholders === [])
                <span class="italic">none for this event</span>
            @else
                @foreach($placeholders as $placeholder)
                    <code>@{{{{ $placeholder }}}}</code>
                @endforeach
            @endif
        </p>

        <x-form.checkbox name="enabled" label="Override enabled" hint="When off, the safe application default is used even if the fields below are filled in." :checked="(bool) old('enabled', $template->enabled)" boxed />

        <x-form.input name="push_title" label="Push title" :value="old('push_title', $template->push_title)" maxlength="120" placeholder="{{ $event['default_title'] }}" optional />
        <x-form.textarea name="push_body" label="Push body" rows="2" :value="old('push_body', $template->push_body)" placeholder="{{ $event['default_body'] }}" optional />
        <x-form.input name="database_title" label="In-app title" :value="old('database_title', $template->database_title)" maxlength="160" placeholder="{{ $event['default_title'] }}" optional />
        <x-form.textarea name="database_body" label="In-app body" rows="2" :value="old('database_body', $template->database_body)" placeholder="{{ $event['default_body'] }}" optional />

        <div class="flex flex-wrap gap-3 border-t border-line pt-6">
            <x-ui.button variant="primary" data-loading-text="Saving…">Save template</x-ui.button>
            <x-ui.button :href="route('admin.settings.notification-templates.index')" variant="secondary">Back</x-ui.button>
        </div>
    </form>
</x-layouts.admin>
