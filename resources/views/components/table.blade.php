{{--
    Data table that scrolls sideways on small screens.
    <x-table :headers="['Name', 'Status', '']">
        <tr> <td class="px-5 py-3.5">…</td> … </tr>
    </x-table>
    An empty header text ('') is announced as "Actions" to screen readers.
--}}
@props(['headers' => [], 'caption' => null])

<div {{ $attributes->class(['relative overflow-x-auto']) }}>
    <table class="w-full min-w-[40rem] text-left text-sm">
        @if ($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        <thead class="border-b border-line bg-canvas/60 text-xs font-semibold tracking-wide text-ink-muted uppercase">
            <tr>
                @foreach ($headers as $header)
                    <th scope="col" class="px-5 py-3 whitespace-nowrap">
                        @if ($header === '') <span class="sr-only">Actions</span> @else {{ $header }} @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            {{ $slot }}
        </tbody>
    </table>
</div>
