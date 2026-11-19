<x-layouts.public :title="$person->name">
    <div class="page-container py-10 sm:py-14">
        <div class="mx-auto max-w-xl">
            <h1 class="sr-only">{{ $person->name }}</h1>
            @include('people._card')
            <p class="mt-4 flex items-start gap-2 text-sm text-ink-muted">
                <x-icon name="lock-closed" class="mt-0.5 size-4" />
                Contact details and locations are private on Medimatch BD. To get in touch about a listing, use the in-app messages.
            </p>
        </div>
    </div>
</x-layouts.public>
