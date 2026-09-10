@props(['title' => 'Nothing here yet', 'message' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200']) }}>
    <h3 class="text-xl font-black text-slate-900">{{ $title }}</h3>
    @if($message)<p class="mt-2 text-slate-600">{{ $message }}</p>@endif
    @if(trim($slot))<div class="mt-2 text-slate-600">{{ $slot }}</div>@endif
</div>
