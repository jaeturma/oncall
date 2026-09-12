@props(['title' => 'Please check the highlighted fields.'])

@if($errors->any())
    <x-ui.alert tone="danger" :title="$title" {{ $attributes }}>
        <ul class="mt-1 list-disc space-y-0.5 pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </x-ui.alert>
@endif
