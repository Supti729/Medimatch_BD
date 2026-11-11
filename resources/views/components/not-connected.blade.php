{{--
    Shown above a form whose backend route does not exist yet.
    <x-not-connected route="login.store" feature="Signing in" />
    Renders nothing once the route exists, so no clean-up is needed later.
--}}
@props(['route', 'feature' => 'This form'])

@unless (Route::has($route))
    <x-alert tone="warning" title="Not connected yet" {{ $attributes }}>
        {{ $feature }} is a design preview. It starts working when the backend is added in the next part of the project.
    </x-alert>
@endunless
