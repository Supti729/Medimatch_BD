{{--
    Label / value pairs.
    <x-detail-list :items="['Strength' => '500 mg', 'Quantity' => '20 tablets']" :columns="3" />
--}}
@props(['items', 'columns' => 2])

<dl {{ $attributes->class(['grid gap-x-6 gap-y-4 text-sm', 'sm:grid-cols-2' => $columns >= 2, 'lg:grid-cols-3' => $columns >= 3]) }}>
    @foreach ($items as $term => $value)
        <div class="min-w-0">
            <dt class="text-ink-muted">{{ $term }}</dt>
            <dd class="mt-0.5 font-medium break-words">{{ $value ?? '—' }}</dd>
        </div>
    @endforeach
</dl>
