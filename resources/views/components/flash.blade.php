@if(session('status'))
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-gold-200 bg-gold-50 p-4 font-semibold text-navy-900']) }} role="status">{{ session('status') }}</div>
@endif
@if(session('error'))
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-red-200 bg-red-50 p-4 font-semibold text-red-800']) }} role="alert">{{ session('error') }}</div>
@endif
