<x-layouts.app title="New request">
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Request a medicine" description="Tell us what you need. Matching listings are shown as soon as you post." />

        <x-alert tone="info" title="Check with your doctor or pharmacist" class="my-6">
            Request only a medicine you have been advised to take. Medimatch BD cannot tell you whether a medicine is right for you.
        </x-alert>

        @include('account.requests._form')
    </div>
</x-layouts.app>
