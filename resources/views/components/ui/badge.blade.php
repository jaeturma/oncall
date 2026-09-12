@props(['tone' => 'neutral', 'icon' => null, 'dot' => false])

<span {{ $attributes->class(['badge', 'badge-'.$tone, 'badge-dot' => $dot]) }}>
    @if($icon)<x-ui.icon :name="$icon" class="size-3.5" />@endif
    {{ $slot }}
</span>
