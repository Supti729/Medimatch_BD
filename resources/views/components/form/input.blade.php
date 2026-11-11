{{--
    Text input with label, hint and validation error.
    <x-form.input name="email" label="Email" type="email" required autocomplete="email" />
    <x-form.input name="q" label="Medicine name" icon="magnifying-glass" hint="Brand or generic name" />

    The previous value is restored automatically after a validation error (never for passwords).
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'icon' => null,
    'id' => null,
    'required' => false,
    'optional' => false,
])

@php
    // $errors is shared by Laravel on web requests; fall back to an empty bag elsewhere.
    $errors ??= new \Illuminate\Support\ViewErrorBag;
    $id ??= str_replace(['.', '[', ']'], '-', $name);
    $hasError = $errors->has($name);
    $current = $type === 'password' ? null : old($name, $value);
    $describedBy = $hasError ? $id.'-error' : ($hint ? $id.'-hint' : null);
@endphp

<x-form.field :name="$name" :id="$id" :label="$label" :hint="$hint" :required="$required" :optional="$optional">
    <div class="relative">
        @if ($icon)
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-ink-muted">
                <x-icon :name="$icon" class="size-5" />
            </span>
        @endif
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}"
            @if (! is_null($current)) value="{{ $current }}" @endif
            @if ($required) required @endif
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class([
                'block min-h-11 w-full rounded-control border bg-surface px-3.5 py-2.5 text-sm text-ink placeholder:text-ink-muted',
                'focus:outline-2 focus:outline-offset-0 disabled:cursor-not-allowed disabled:bg-canvas disabled:text-ink-muted',
                'pl-11' => $icon,
                'border-line focus:border-brand-600 focus:outline-brand-600/30' => ! $hasError,
                'border-danger-600 focus:border-danger-600 focus:outline-danger-600/30' => $hasError,
            ]) }}>
    </div>
</x-form.field>
