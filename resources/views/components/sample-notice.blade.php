{{-- Marks a page or section as showing invented sample data. Remove once real data is connected. --}}
<x-alert tone="info" title="Sample data" {{ $attributes }}>
    {{ $slot->isNotEmpty() ? $slot : 'These listings are invented examples for the prototype. They are not real offers of medicine.' }}
</x-alert>
