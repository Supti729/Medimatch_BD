<x-layouts.app title="New listing" :preview="$usingSampleData">
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Donate a medicine" description="Your listing is reviewed before it becomes visible. It takes about three minutes to fill in." />

        <x-alert tone="info" title="Before you start" class="my-6">
            Only sealed, unexpired medicines in original packaging can be listed. Controlled medicines, injections and medicines that need refrigeration are not allowed.
        </x-alert>

        @include('account.listings._form', ['listing' => null, 'location' => $user, 'storeRoute' => 'listings.store'])
    </div>
</x-layouts.app>
