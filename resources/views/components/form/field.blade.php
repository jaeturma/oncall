@props(['name' => null, 'label' => null, 'hint' => null, 'required' => false, 'for' => null, 'optional' => false])

@php
    $id = $for ?? ($name ? str($name)->replace(['[', ']'], ['_', ''])->toString() : null);
    $errorKey = $name ? str($name)->replace(['[', ']'], ['.', ''])->rtrim('.')->toString() : null;
    $hasError = $errorKey && $errors->has($errorKey);
@endphp

<div {{ $attributes->class(['field']) }} @if($hasError) data-has-error @endif>
    @if($label)
        <label class="field-label" @if($id) for="{{ $id }}" @endif>
            {{ $label }}
            @if($required)<span class="required-mark" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
            @if($optional)<span class="ml-1 text-xs font-normal text-ink-muted">Optional</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if($hint && ! $hasError)<p class="field-hint" @if($id) id="{{ $id }}-hint" @endif>{{ $hint }}</p>@endif
    @if($hasError)
        <p class="field-error" @if($id) id="{{ $id }}-error" @endif><x-ui.icon name="exclamation-triangle" class="mt-0.5 size-4" />{{ $errors->first($errorKey) }}</p>
    @endif
</div>
