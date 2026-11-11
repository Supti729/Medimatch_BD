{{--
    Shows one-time messages set by controllers, for example:
    return back()->with('success', 'Profile updated.');
    Keys: success | status | warning | error
--}}
@php
    $messages = array_filter([
        'success' => session('success') ?? session('status'),
        'warning' => session('warning'),
        'danger' => session('error'),
    ]);
@endphp

@if ($messages)
    <div {{ $attributes->class(['space-y-3']) }}>
        @foreach ($messages as $tone => $message)
            <x-alert :tone="$tone" dismissible>{{ $message }}</x-alert>
        @endforeach
    </div>
@endif
