{{--
    Dropdown list with label, hint and validation error.
    <x-form.select name="division" label="Division" :options="$divisions" placeholder="Select division" required />

    options: [value => label]. You can also write <option> tags inside the component instead.
--}}
@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'hint' => null,
    'id' => null,
    'required' => false,
    'optional' => false,
])

@php
    // $errors is shared by Laravel on web requests; fall back to an empty bag elsewhere.
    $errors ??= new \Illuminate\Support\ViewErrorBag;
    $id ??= str_replace(['.', '[', ']'], '-', $name);
    $hasError = $errors->has($name);
    $current = old($name, $selected);
    $describedBy = $hasError ? $id.'-error' : ($hint ? $id.'-hint' : null);
@endphp

<x-form.field :name="$name" :id="$id" :label="$label" :hint="$hint" :required="$required" :optional="$optional">
    <div class="relative">
        <select name="{{ $name }}" id="{{ $id }}"
            @if ($required) required @endif
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class([
                'block min-h-11 w-full appearance-none rounded-control border bg-surface py-2.5 pr-10 pl-3.5 text-sm text-ink',
                'focus:outline-2 focus:outline-offset-0 disabled:cursor-not-allowed disabled:bg-canvas disabled:text-ink-muted',
                'border-line focus:border-brand-600 focus:outline-brand-600/30' => ! $hasError,
                'border-danger-600 focus:border-danger-600 focus:outline-danger-600/30' => $hasError,
            ]) }}>
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
            {{ $slot }}
        </select>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-ink-muted">
            <x-icon name="chevron-down" class="size-4" />
        </span>
    </div>
</x-form.field>
