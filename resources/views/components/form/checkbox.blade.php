@props(['name', 'label', 'hint' => null, 'value' => '1', 'checked' => false, 'required' => false, 'boxed' => false])

@php
    $id = str($name)->replace(['[', ']'], ['_', ''])->toString().'_'.str($value)->slug();
    $errorKey = str($name)->replace(['[', ']'], ['.', ''])->rtrim('.')->toString();
    $hasError = $errors->has($errorKey);
@endphp

<div class="grid gap-1.5" @if($hasError) data-has-error @endif>
    <label {{ $attributes->class(['flex cursor-pointer items-start gap-3', 'choice' => $boxed]) }} for="{{ $id }}">
        <input
            id="{{ $id }}"
            class="checkbox mt-0.5"
            type="checkbox"
            name="{{ $name }}"
            value="{{ $value }}"
            @checked($checked)
            @if($required) required aria-required="true" @endif
            @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        >
        <span class="min-w-0">
            <span class="block text-sm font-medium text-ink">{{ $label }}</span>
            @if($hint)<span class="mt-0.5 block text-sm text-ink-muted">{{ $hint }}</span>@endif
        </span>
    </label>
    @if($hasError)
        <p class="field-error" id="{{ $id }}-error"><x-ui.icon name="exclamation-triangle" class="mt-0.5 size-4" />{{ $errors->first($errorKey) }}</p>
    @endif
</div>
