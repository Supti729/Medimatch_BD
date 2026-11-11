{{--
    Multi-line text box with label, hint and validation error.
    <x-form.textarea name="notes" label="Additional information" rows="4" optional />
--}}
@props([
    'name',
    'label' => null,
    'value' => null,
    'hint' => null,
    'rows' => 4,
    'id' => null,
    'required' => false,
    'optional' => false,
])

@php
    // $errors is shared by Laravel on web requests; fall back to an empty bag elsewhere.
    $errors ??= new \Illuminate\Support\ViewErrorBag;
    $id ??= str_replace(['.', '[', ']'], '-', $name);
    $hasError = $errors->has($name);
    $describedBy = $hasError ? $id.'-error' : ($hint ? $id.'-hint' : null);
@endphp

<x-form.field :name="$name" :id="$id" :label="$label" :hint="$hint" :required="$required" :optional="$optional">
    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'block w-full rounded-control border bg-surface px-3.5 py-2.5 text-sm text-ink placeholder:text-ink-muted',
            'focus:outline-2 focus:outline-offset-0 disabled:cursor-not-allowed disabled:bg-canvas',
            'border-line focus:border-brand-600 focus:outline-brand-600/30' => ! $hasError,
            'border-danger-600 focus:border-danger-600 focus:outline-danger-600/30' => $hasError,
        ]) }}>{{ old($name, $value) }}</textarea>
</x-form.field>
