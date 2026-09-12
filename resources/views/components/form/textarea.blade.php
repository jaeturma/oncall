@props(['name', 'label' => null, 'hint' => null, 'required' => false, 'optional' => false, 'value' => null, 'rows' => 4, 'wrapperClass' => ''])

@php
    $id = str($name)->replace(['[', ']'], ['_', ''])->toString();
    $errorKey = str($name)->replace(['[', ']'], ['.', ''])->rtrim('.')->toString();
    $hasError = $errors->has($errorKey);
    $described = collect([$hint && ! $hasError ? $id.'-hint' : null, $hasError ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<x-form.field :name="$name" :label="$label" :hint="$hint" :required="$required" :optional="$optional" :for="$id" class="{{ $wrapperClass }}">
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if($required) required aria-required="true" @endif
        @if($hasError) aria-invalid="true" @endif
        @if($described) aria-describedby="{{ $described }}" @endif
        {{ $attributes->class(['textarea', 'textarea-invalid' => $hasError]) }}
    >{{ old($errorKey, $value) }}</textarea>
</x-form.field>
