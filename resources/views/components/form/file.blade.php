@props(['name', 'label' => null, 'hint' => null, 'required' => false, 'accept' => null, 'wrapperClass' => ''])

@php
    $id = str($name)->replace(['[', ']'], ['_', ''])->toString();
    $hasError = $errors->has($name);
    $described = collect([$hint && ! $hasError ? $id.'-hint' : null, $hasError ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<x-form.field :name="$name" :label="$label" :hint="$hint" :required="$required" :for="$id" class="{{ $wrapperClass }}">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="file"
        @if($accept) accept="{{ $accept }}" @endif
        @if($required) required aria-required="true" @endif
        @if($hasError) aria-invalid="true" @endif
        @if($described) aria-describedby="{{ $described }}" @endif
        {{ $attributes->class(['file-input', 'border-danger-500' => $hasError]) }}
    >
</x-form.field>
