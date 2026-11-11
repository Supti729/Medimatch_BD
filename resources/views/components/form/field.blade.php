{{--
    Wrapper used by the form inputs: label, hint text and validation error.
    You normally use <x-form.input>, <x-form.select> or <x-form.textarea> instead of this directly.
--}}
@props(['name', 'id', 'label' => null, 'hint' => null, 'required' => false, 'optional' => false])

@php
    // $errors is shared by Laravel on web requests; fall back to an empty bag elsewhere.
    $errors ??= new \Illuminate\Support\ViewErrorBag;
    $error = $errors->first($name);
@endphp

<div {{ $attributes }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-ink">
            {{ $label }}
            @if ($required)
                <span class="text-danger-600" aria-hidden="true">*</span>
            @elseif ($optional)
                <span class="font-normal text-ink-muted">(optional)</span>
            @endif
        </label>
    @endif

    <div @class(['mt-1.5' => $label])>{{ $slot }}</div>

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-start gap-1.5 text-sm text-danger-700">
            <x-icon name="exclamation-circle" class="mt-0.5 size-4" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-sm text-ink-muted">{{ $hint }}</p>
    @endif
</div>
