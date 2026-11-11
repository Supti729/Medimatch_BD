<x-layouts.auth title="Auth layout preview" heading="Auth layout" subheading="Used by login, registration and password pages. This form is a visual sample and does not sign anyone in.">
    <form class="space-y-5" onsubmit="event.preventDefault()">
        <x-form.input name="login" label="Email or phone number" placeholder="you@example.com or 01XXXXXXXXX" autocomplete="username" />
        <x-form.input name="password" label="Password" type="password" autocomplete="current-password" />
        <x-button type="submit" block disabled>Log in (available in a later step)</x-button>
    </form>
    <x-slot:footer><a href="{{ route('styleguide') }}" class="font-medium text-brand-700 hover:underline">Back to the style guide</a></x-slot:footer>
</x-layouts.auth>
