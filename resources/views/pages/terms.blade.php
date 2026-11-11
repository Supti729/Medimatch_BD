<x-layouts.public title="Terms and conditions">
    <x-page-hero eyebrow="Legal" title="Terms and conditions" description="The rules for using Medimatch BD." />

    <div class="page-container py-12 sm:py-16">
        <x-alert tone="warning" title="Draft for an academic prototype" class="max-w-3xl">
            These terms describe the intended design. They have not been reviewed by a lawyer and do not create a real service.
        </x-alert>

        <x-prose class="mt-8">
            <h2>1. About the platform</h2>
            <p>Medimatch BD is a university capstone prototype. It is not a pharmacy, a healthcare provider or an authorized medicine distribution service, and it must not be used to exchange real medicines.</p>

            <h2>2. No medical advice</h2>
            <p>Nothing on the platform is medical advice. Listings, matches and search results do not mean a medicine is safe or suitable for you. Always consult a registered doctor or pharmacist.</p>

            <h2>3. Your account</h2>
            <ul>
                <li>You must give accurate information and keep your password private.</li>
                <li>One person may hold one account. The same account can both request and donate.</li>
                <li>You are responsible for activity on your account.</li>
            </ul>

            <h2>4. Listing rules</h2>
            <ul>
                <li>List only medicines that meet the <a href="{{ route('safety') }}">safety guidelines</a>.</li>
                <li>Describe the medicine truthfully, including its expiry date and packaging condition.</li>
                <li>Individuals may only donate. Asking for money for a donation is not allowed.</li>
                <li>“Affordable access” listings are limited to authorized partner organizations.</li>
            </ul>

            <h2>5. Request rules</h2>
            <ul>
                <li>Request only what you genuinely need, in a reasonable quantity.</li>
                <li>Provide a valid prescription when a verification check is required.</li>
                <li>Do not request medicines in order to resell them.</li>
            </ul>

            <h2>6. Conduct</h2>
            <p>Do not harass other users, ask for their personal details, post false information, or try to access accounts or documents that are not yours.</p>

            <h2>7. Moderation</h2>
            <p>Moderators may review, reject or block listings, and suspend accounts that break these terms. Moderation actions are recorded.</p>

            <h2>8. Responsibility</h2>
            <p>Users are responsible for the medicines they offer and accept. The project team provides the prototype “as is” for demonstration and evaluation only.</p>

            <h2>9. Changes</h2>
            <p>These terms will change as the project develops. See also the <a href="{{ route('privacy') }}">privacy policy</a>.</p>
        </x-prose>
    </div>
</x-layouts.public>
