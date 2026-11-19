{{-- "View as table" fallback under every chart, so no value is available by colour or hover alone. --}}
@props(['data', 'labelHeading' => 'Category', 'valueHeading' => 'Count'])

<details {{ $attributes->class(['mt-4 text-sm']) }}>
    <summary class="inline-flex min-h-8 cursor-pointer items-center rounded-control text-xs font-medium text-ink-muted hover:text-brand-700">View as table</summary>
    <table class="mt-2 w-full max-w-xs text-left text-sm">
        <thead class="text-xs text-ink-muted">
            <tr><th scope="col" class="py-1 pr-4 font-medium">{{ $labelHeading }}</th><th scope="col" class="py-1 text-right font-medium">{{ $valueHeading }}</th></tr>
        </thead>
        <tbody class="divide-y divide-line">
            @foreach ($data as $label => $value)
                <tr><th scope="row" class="py-1 pr-4 font-normal text-ink-soft">{{ $label }}</th><td class="py-1 text-right font-medium tabular-nums">{{ $value }}</td></tr>
            @endforeach
        </tbody>
    </table>
</details>
