@if(session('status') || session('error') || session('warning'))
    <div {{ $attributes->class(['grid gap-3']) }}>
        @if(session('status'))
            <x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>
        @endif
        @if(session('warning'))
            <x-ui.alert tone="warning">{{ session('warning') }}</x-ui.alert>
        @endif
        @if(session('error'))
            <x-ui.alert tone="danger">{{ session('error') }}</x-ui.alert>
        @endif
    </div>
@endif
