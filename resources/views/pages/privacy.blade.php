<x-layouts.public title="Privacy policy">
    <x-page-hero eyebrow="Legal" title="Privacy policy" description="What information Medimatch BD is designed to collect, and who can see it." />

    <div class="page-container py-12 sm:py-16">
        <x-alert tone="warning" title="Draft for an academic prototype" class="max-w-3xl">
            This policy describes the intended design. It has not been reviewed by a lawyer and the prototype should not be used with real personal or medical data.
        </x-alert>

        <x-prose class="mt-8">
            <h2>1. Information we collect</h2>
            <ul>
                <li><strong>Account details:</strong> name, email address and/or phone number, password (stored only as a secure hash), and an optional profile picture.</li>
                <li><strong>Location:</strong> your division, district and upazila. We do not collect your street address or GPS position.</li>
                <li><strong>Listings and requests:</strong> the medicine details you enter, such as name, strength, quantity and expiry date.</li>
                <li><strong>Messages:</strong> conversations you have with other users inside the platform.</li>
                <li><strong>Verification documents:</strong> prescriptions or other documents you upload when a verification check is required.</li>
            </ul>

            <h2>2. What other people can see</h2>
            <p>Other users can see your name, profile picture, join date, a verification indicator and the area of your listings.</p>
            <p>Other users can <strong>never</strong> see your phone number, email address, exact address, uploaded documents, or your activity with other people.</p>

            <h2>3. Verification documents</h2>
            <ul>
                <li>Documents are kept in private storage that cannot be reached by a public link.</li>
                <li>Only authorized reviewers can open them, and each access is recorded.</li>
                <li>Donors are told only whether verification passed, never the document itself.</li>
                <li>Artificial intelligence is not used to approve or reject prescriptions.</li>
            </ul>

            <h2>4. How information is used</h2>
            <ul>
                <li>To run your account and show your listings and requests.</li>
                <li>To match requests with listings, including automated text matching.</li>
                <li>To send you notifications about your activity.</li>
                <li>To moderate the platform and investigate reports.</li>
            </ul>

            <h2>5. What we do not do</h2>
            <ul>
                <li>We do not sell personal information or prescription data.</li>
                <li>We do not show advertising based on your health information.</li>
                <li>We do not share your contact details with other users or sponsors.</li>
            </ul>

            <h2>6. Your choices</h2>
            <p>You can edit your profile, change your privacy settings, cancel listings and requests, and ask for your account to be closed.</p>

            <h2>7. Security</h2>
            <p>Passwords are hashed, access to sensitive pages is checked on the server, and administrative actions are written to an audit log. No system is perfectly secure, so please do not upload anything you are not comfortable sharing with a reviewer.</p>

            <h2>8. Contact</h2>
            <p>Questions about this policy can be sent through the <a href="{{ route('contact') }}">contact page</a>.</p>
        </x-prose>
    </div>
</x-layouts.public>
