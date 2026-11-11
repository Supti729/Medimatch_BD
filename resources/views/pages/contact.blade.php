<x-layouts.public title="Contact">
    <x-page-hero eyebrow="Contact" title="Get in touch with the project team"
        description="Questions, feedback or a partnership idea? Send us a message." />

    <div class="page-container grid gap-8 py-12 sm:py-16 lg:grid-cols-[1.3fr_1fr]">
        <x-card title="Send a message">
            <x-not-connected route="contact.store" feature="The contact form" class="mb-5" />

            <form class="grid gap-5 sm:grid-cols-2"
                @if (Route::has('contact.store')) method="POST" action="{{ route('contact.store') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
                @csrf
                <x-form.input name="name" label="Your name" required maxlength="100" autocomplete="name" />
                <x-form.input name="email" label="Email" type="email" required maxlength="150" autocomplete="email" hint="Used only to reply to you." />
                <div class="sm:col-span-2">
                    <x-form.select name="topic" label="Topic" placeholder="Choose a topic" required
                        :options="['general' => 'General question', 'safety' => 'Safety concern', 'partnership' => 'Partnership (NGO, hospital, pharmacy)', 'feedback' => 'Feedback on the prototype']" />
                </div>
                <div class="sm:col-span-2">
                    <x-form.textarea name="message" label="Message" rows="5" required maxlength="2000"
                        hint="Please do not include prescriptions or medical details." />
                </div>
                <div class="sm:col-span-2">
                    <x-button type="submit" :disabled="! Route::has('contact.store')">Send message</x-button>
                </div>
            </form>
        </x-card>

        <aside class="space-y-6">
            <x-alert tone="danger" title="Not for emergencies">
                We cannot give medical advice or urgent help. In an emergency, go to the nearest hospital or call 999.
            </x-alert>

            <x-card title="Report a listing or user">
                <p class="text-sm text-ink-soft">The fastest way is the Report button on the listing itself, so moderators see exactly what you mean.</p>
                <x-button :href="route('medicines.index')" variant="secondary" size="sm" class="mt-4">Browse medicines</x-button>
            </x-card>

            <x-card title="Organizations">
                <p class="text-sm text-ink-soft">
                    NGOs, hospitals and licensed pharmacies interested in a future pilot can choose “Partnership” in the form.
                    Partner accounts are a planned feature.
                </p>
            </x-card>
        </aside>
    </div>
</x-layouts.public>
