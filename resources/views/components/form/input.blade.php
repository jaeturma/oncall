@props(['name', 'label' => null, 'hint' => null, 'required' => false, 'optional' => false, 'type' => 'text', 'value' => null, 'size' => null, 'wrapperClass' => ''])

@php
    $id = str($name)->replace(['[', ']'], ['_', ''])->toString();
    $errorKey = str($name)->replace(['[', ']'], ['.', ''])->rtrim('.')->toString();
    $hasError = $errors->has($errorKey);
    $described = collect([$hint && ! $hasError ? $id.'-hint' : null, $hasError ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<x-form.field :name="$name" :label="$label" :hint="$hint" :required="$required" :optional="$optional" :for="$id" class="{{ $wrapperClass }}">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if($type !== 'password' && $type !== 'file') value="{{ old($errorKey, $value) }}" @endif
        @if($required) required aria-required="true" @endif
        @if($hasError) aria-invalid="true" @endif
        @if($described) aria-describedby="{{ $described }}" @endif
        {{ $attributes->class(['input', 'input-lg' => $size === 'lg', 'input-invalid' => $hasError]) }}
    >
</x-form.field>
