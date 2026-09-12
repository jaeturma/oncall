@props(['status' => null, 'available' => null, 'size' => 'sm', 'onDark' => false])

@php
    use App\Enums\AvailabilityStatus;

    /**
     * Accepts either the four-state enum ($status) or, for older call sites,
     * a plain boolean ($available). Text always names the state; color and
     * the pulse dot are never the only signal.
     */
    $resolved = $status instanceof AvailabilityStatus
        ? $status
        : ($available !== null
            ? ($available ? AvailabilityStatus::Available : AvailabilityStatus::Offline)
            : AvailabilityStatus::Offline);

    $palette = match ($resolved) {
        AvailabilityStatus::Available => ['dot' => 'bg-success-500', 'tone' => 'bg-success-50 text-success-800', 'pulse' => true],
        AvailabilityStatus::ByAppointment => ['dot' => 'bg-info-600', 'tone' => 'bg-info-50 text-info-800', 'pulse' => false],
        AvailabilityStatus::Busy => ['dot' => 'bg-warning-500', 'tone' => 'bg-warning-50 text-warning-800', 'pulse' => false],
        AvailabilityStatus::Offline => ['dot' => 'bg-slate-400', 'tone' => 'bg-slate-100 text-slate-600', 'pulse' => false],
    };
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full font-semibold whitespace-nowrap',
    'px-2.5 py-1 text-xs' => $size === 'sm',
    'px-3 py-1.5 text-sm' => $size === 'md',
    $palette['tone'] => ! $onDark,
    'bg-white/10 text-white' => $onDark,
]) }} title="{{ $resolved->description() }}">
    <span class="relative flex size-2">
        @if($palette['pulse'])<span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $palette['dot'] }} opacity-60 motion-reduce:hidden"></span>@endif
        <span class="relative inline-flex size-2 rounded-full {{ $palette['dot'] }}"></span>
    </span>
    {{ $resolved->label() }}
</span>
