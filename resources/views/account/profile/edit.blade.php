@php $ready = Route::has('profile.update'); @endphp

<x-layouts.app title="Edit profile" :preview="$usingSampleData">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('profile.show') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700">
            <x-icon name="arrow-left" class="size-4" /> Profile
        </a>
        <x-page-header class="mt-3" title="Edit profile" />

        <form class="mt-6 space-y-6" enctype="multipart/form-data" @if ($ready) method="POST" action="{{ route('profile.update') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
            @csrf
            @if ($ready) @method('PUT') @endif

            <x-card title="About you">
                <div class="grid gap-5">
                    <x-form.input name="name" label="Full name" :value="$user['name']" required maxlength="100" autocomplete="name" hint="Shown on your public profile." />
                    <div>
                        @php $errors ??= new \Illuminate\Support\ViewErrorBag; @endphp
                        <label for="avatar" class="block text-sm font-medium">Profile picture <span class="font-normal text-ink-muted">(optional)</span></label>
                        <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/webp" aria-describedby="avatar-hint"
                            class="mt-1.5 block w-full rounded-control border border-line bg-surface text-sm text-ink-soft file:mr-4 file:min-h-11 file:cursor-pointer file:border-0 file:bg-brand-50 file:px-4 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100">
                        @error('avatar')
                            <p class="mt-1.5 text-sm text-danger-700">{{ $message }}</p>
                        @else
                            <p id="avatar-hint" class="mt-1.5 text-sm text-ink-muted">JPG, PNG or WebP, up to 2 MB.</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <x-card title="Your area" description="Used to show medicines near you and to pre-fill your listings and requests.">
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-form.location required with-upazila :division="$user['division']" :district="$user['district']" :upazila="$user['upazila']" />
                </div>
            </x-card>

            <x-card title="Contact details" description="Private. Used for logging in and account notices only.">
                <x-detail-list :items="['Email' => $user['email'], 'Phone number' => $user['phone']]" />
                <x-slot:footer>
                    <a href="{{ route('settings.security') }}" class="text-sm font-semibold text-brand-700 hover:underline">Change email or phone number in Security settings</a>
                </x-slot:footer>
            </x-card>

            <x-not-connected route="profile.update" feature="Saving your profile" />

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-button :href="route('profile.show')" variant="ghost">Cancel</x-button>
                <x-button type="submit" :disabled="! $ready">Save changes</x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
