{{--
    Design system preview. Local development only (see routes/web.php).
    Shows every design token defined in resources/css/app.css.
--}}
@php
    $scales = [
        'brand' => ['label' => 'Brand (teal)', 'note' => 'Main actions, links, active states. brand-600 is the primary button colour.', 'steps' => [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]],
        'accent' => ['label' => 'Accent (coral)', 'note' => 'Donation and community highlights. Use sparingly.', 'steps' => [50, 100, 200, 400, 500, 600, 700]],
    ];

    // Full class names are written out so Tailwind can find them.
    $swatches = [
        'brand' => [50 => 'bg-brand-50', 100 => 'bg-brand-100', 200 => 'bg-brand-200', 300 => 'bg-brand-300', 400 => 'bg-brand-400', 500 => 'bg-brand-500', 600 => 'bg-brand-600', 700 => 'bg-brand-700', 800 => 'bg-brand-800', 900 => 'bg-brand-900', 950 => 'bg-brand-950'],
        'accent' => [50 => 'bg-accent-50', 100 => 'bg-accent-100', 200 => 'bg-accent-200', 400 => 'bg-accent-400', 500 => 'bg-accent-500', 600 => 'bg-accent-600', 700 => 'bg-accent-700'],
    ];

    $surfaces = [
        ['canvas', 'bg-canvas', 'Page background'],
        ['surface', 'bg-surface', 'Cards, panels, inputs'],
        ['line', 'bg-line', 'Borders and dividers'],
        ['ink', 'bg-ink', 'Main text'],
        ['ink-soft', 'bg-ink-soft', 'Secondary text'],
        ['ink-muted', 'bg-ink-muted', 'Hints and captions'],
    ];

    $statuses = [
        ['Success', 'bg-success-50 text-success-700 ring-success-600/20', 'bg-success-600', 'Available, verified, completed'],
        ['Warning', 'bg-warning-50 text-warning-700 ring-warning-600/20', 'bg-warning-600', 'Pending review, expiring soon'],
        ['Danger', 'bg-danger-50 text-danger-700 ring-danger-600/20', 'bg-danger-600', 'Rejected, blocked, errors'],
        ['Info', 'bg-info-50 text-info-700 ring-info-600/20', 'bg-info-600', 'Requested, reserved, notices'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>Style Guide · {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <main class="page-container py-10 sm:py-14">
            <header class="max-w-2xl">
                <p class="text-sm font-semibold text-brand-700">Development only</p>
                <h1 class="mt-2 text-3xl sm:text-4xl">Medimatch BD style guide</h1>
                <p class="mt-3 text-ink-soft">
                    The design tokens every page shares. They are defined once in
                    <code class="rounded bg-brand-50 px-1.5 py-0.5 text-sm text-brand-800">resources/css/app.css</code>.
                </p>
            </header>

            {{-- Colour scales --}}
            <section class="mt-12" aria-labelledby="sg-colour">
                <h2 id="sg-colour" class="text-xl">Colour</h2>

                @foreach ($scales as $name => $scale)
                    <div class="mt-6">
                        <h3 class="text-base">{{ $scale['label'] }}</h3>
                        <p class="mt-1 text-sm text-ink-muted">{{ $scale['note'] }}</p>
                        <ul class="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-11">
                            @foreach ($scale['steps'] as $step)
                                <li>
                                    <div class="h-14 rounded-control ring-1 ring-ink/10 ring-inset {{ $swatches[$name][$step] }}"></div>
                                    <p class="mt-1.5 text-xs text-ink-soft">{{ $name }}-{{ $step }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach

                <div class="mt-8">
                    <h3 class="text-base">Surfaces and text</h3>
                    <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        @foreach ($surfaces as [$token, $class, $use])
                            <li>
                                <div class="h-14 rounded-control ring-1 ring-ink/10 ring-inset {{ $class }}"></div>
                                <p class="mt-1.5 text-xs font-medium text-ink">{{ $token }}</p>
                                <p class="text-xs text-ink-muted">{{ $use }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-8">
                    <h3 class="text-base">Status</h3>
                    <p class="mt-1 text-sm text-ink-muted">Status is always shown with a text label, never colour alone.</p>
                    <ul class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($statuses as [$label, $badge, $solid, $use])
                            <li class="rounded-card border border-line bg-surface p-4 shadow-card">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $badge }}">{{ $label }}</span>
                                    <span class="size-4 rounded-full {{ $solid }}" aria-hidden="true"></span>
                                </div>
                                <p class="mt-3 text-sm text-ink-soft">{{ $use }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            {{-- Typography --}}
            <section class="mt-14" aria-labelledby="sg-type">
                <h2 id="sg-type" class="text-xl">Typography</h2>
                <div class="mt-4 divide-y divide-line rounded-card border border-line bg-surface shadow-card">
                    <div class="p-5">
                        <p class="text-xs text-ink-muted">Page title · text-4xl / semibold</p>
                        <p class="mt-1 text-4xl font-semibold tracking-tight">Find the medicine you need.</p>
                    </div>
                    <div class="p-5">
                        <p class="text-xs text-ink-muted">Section heading · text-2xl / semibold</p>
                        <p class="mt-1 text-2xl font-semibold tracking-tight">Share what someone else needs</p>
                    </div>
                    <div class="p-5">
                        <p class="text-xs text-ink-muted">Card heading · text-lg / semibold</p>
                        <p class="mt-1 text-lg font-semibold">Paracetamol 500 mg tablet</p>
                    </div>
                    <div class="p-5">
                        <p class="text-xs text-ink-muted">Body · text-base</p>
                        <p class="mt-1 max-w-prose text-ink-soft">
                            Medimatch BD connects people who need eligible medicines with donors and authorized
                            partners near them, while keeping personal details private.
                        </p>
                    </div>
                    <div class="p-5">
                        <p class="text-xs text-ink-muted">Caption · text-sm / ink-muted</p>
                        <p class="mt-1 text-sm text-ink-muted">Listed 2 days ago · Dhanmondi, Dhaka</p>
                    </div>
                    <div class="p-5">
                        <p class="text-xs text-ink-muted">Bangla · Hind Siliguri</p>
                        <p lang="bn" class="mt-1 text-lg">আপনার প্রয়োজনীয় ওষুধ খুঁজুন। অন্যের প্রয়োজনে পাশে দাঁড়ান।</p>
                    </div>
                </div>
            </section>

            {{-- Shape and depth --}}
            <section class="mt-14" aria-labelledby="sg-shape">
                <h2 id="sg-shape" class="text-xl">Corners and shadows</h2>
                <ul class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <li class="rounded-control border border-line bg-surface p-5">
                        <p class="text-sm font-medium">rounded-control</p>
                        <p class="mt-1 text-sm text-ink-muted">Buttons and inputs</p>
                    </li>
                    <li class="rounded-card bg-surface p-5 shadow-card">
                        <p class="text-sm font-medium">rounded-card + shadow-card</p>
                        <p class="mt-1 text-sm text-ink-muted">Cards and panels</p>
                    </li>
                    <li class="rounded-card bg-surface p-5 shadow-raised">
                        <p class="text-sm font-medium">shadow-raised</p>
                        <p class="mt-1 text-sm text-ink-muted">Hover state, dropdowns</p>
                    </li>
                    <li class="rounded-card bg-surface p-5 shadow-overlay">
                        <p class="text-sm font-medium">shadow-overlay</p>
                        <p class="mt-1 text-sm text-ink-muted">Modals</p>
                    </li>
                </ul>
            </section>

            {{-- Tokens in use --}}
            <section class="mt-14" aria-labelledby="sg-sample">
                <h2 id="sg-sample" class="text-xl">Tokens in use</h2>
                <p class="mt-1 text-sm text-ink-muted">
                    A preview only. Reusable button, input and card components arrive in step 1.4.
                </p>
                <div class="mt-4 grid gap-5 lg:grid-cols-2">
                    <div class="rounded-card border border-line bg-surface p-6 shadow-card">
                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="rounded-control bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">Find medicine</button>
                            <button type="button" class="rounded-control border border-line bg-surface px-4 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-brand-50">Request medicine</button>
                            <button type="button" class="rounded-control bg-accent-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-accent-700">Donate medicine</button>
                        </div>
                        <label for="sg-input" class="mt-6 block text-sm font-medium">Medicine name</label>
                        <input id="sg-input" type="text" placeholder="e.g. Napa 500 mg"
                            class="mt-1.5 w-full rounded-control border border-line bg-surface px-3.5 py-2.5 text-sm placeholder:text-ink-muted focus:border-brand-600 focus:outline-2 focus:outline-offset-0 focus:outline-brand-600/30">
                        <p class="mt-1.5 text-xs text-ink-muted">Press Tab to see the keyboard focus style.</p>
                    </div>

                    <article class="rounded-card border border-line bg-surface p-6 shadow-card transition-shadow hover:shadow-raised">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg">Napa 500 mg</h3>
                                <p class="text-sm text-ink-muted">Paracetamol · Tablet</p>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700 ring-1 ring-success-600/20 ring-inset">Available</span>
                        </div>
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-ink-muted">Quantity</dt>
                                <dd class="font-medium">20 tablets</dd>
                            </div>
                            <div>
                                <dt class="text-ink-muted">Location</dt>
                                <dd class="font-medium">Dhanmondi, Dhaka</dd>
                            </div>
                        </dl>
                        <p class="mt-4 text-xs text-ink-muted">Sample content for the style guide. Not a real listing.</p>
                    </article>
                </div>
            </section>
        </main>
    </body>
</html>
