{{--
    Checkbox with its label beside it.
    <x-form.checkbox name="remember" label="Remember me" />
    <x-form.checkbox name="terms" required>I agree to the <a href="/terms">terms</a></x-form.checkbox>
--}}
@props(['name', 'label' => null, 'value' => '1', 'checked' => false, 'hint' => null, 'id' => null, 'required' => false])

@php
    // $errors is shared by Laravel on web requests; fall back to an empty bag elsewhere.
    $errors ??= new \Illuminate\Support\ViewErrorBag;
    $id ??= str_replace(['.', '[', ']'], '-', $name);
    $error = $errors->first($name);
    $isChecked = old() ? (bool) old($name) : (bool) $checked;
@endphp

<div>
    <div class="flex items-start gap-3">
        <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}"
            @checked($isChecked)
            @if ($required) required @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->class(['mt-0.5 size-5 shrink-0 rounded border-line accent-brand-600']) }}>
        <label for="{{ $id }}" class="text-sm text-ink-soft">
            {{ $label ?? $slot }}
            @if ($hint)
                <span class="mt-0.5 block text-ink-muted">{{ $hint }}</span>
            @endif
        </label>
    </div>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 text-sm text-danger-700">{{ $error }}</p>
    @endif
</div>
