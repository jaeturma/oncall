@props(['name', 'label' => null, 'hint' => null, 'required' => false, 'optional' => false, 'placeholder' => null, 'size' => null, 'wrapperClass' => ''])

@php
    $id = str($name)->replace(['[', ']'], ['_', ''])->toString();
    $errorKey = str($name)->replace(['[', ']'], ['.', ''])->rtrim('.')->toString();
    $hasError = $errors->has($errorKey);
    $described = collect([$hint && ! $hasError ? $id.'-hint' : null, $hasError ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<x-form.field :name="$name" :label="$label" :hint="$hint" :required="$required" :optional="$optional" :for="$id" class="{{ $wrapperClass }}">
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if($required) required aria-required="true" @endif
        @if($hasError) aria-invalid="true" @endif
        @if($described) aria-describedby="{{ $described }}" @endif
        {{ $attributes->class(['select', 'select-lg' => $size === 'lg', 'select-invalid' => $hasError]) }}
    >
        @if($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        {{ $slot }}
    </select>
</x-form.field>
