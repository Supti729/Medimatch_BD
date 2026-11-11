{{-- Readable long-form text (policy and information pages). Styles plain h2, h3, p, ul and ol inside it. --}}
<div {{ $attributes->class([
    'max-w-3xl text-ink-soft',
    '[&_h2]:mt-10 [&_h2]:mb-3 [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-ink',
    '[&_h3]:mt-6 [&_h3]:mb-2 [&_h3]:text-base [&_h3]:font-semibold [&_h3]:text-ink',
    '[&_p]:mt-3 [&_p]:leading-relaxed',
    '[&_ul]:mt-3 [&_ul]:list-disc [&_ul]:space-y-1.5 [&_ul]:pl-5',
    '[&_ol]:mt-3 [&_ol]:list-decimal [&_ol]:space-y-1.5 [&_ol]:pl-5',
    '[&_a]:font-medium [&_a]:text-brand-700 [&_a]:underline',
    '[&_strong]:font-semibold [&_strong]:text-ink',
]) }}>
    {{ $slot }}
</div>
