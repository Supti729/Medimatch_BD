{{--
    Profile picture, or the first letter of the name when there is no picture.
    <x-avatar :user="$user" />                 the user's own view: always shows their picture
    <x-avatar :user="$person" public />        someone else's view: respects their "show picture" setting
    size: sm | md | lg | xl
--}}
@props(['user', 'size' => 'md', 'public' => false])

@php
    $sizes = ['sm' => 'size-9 text-sm', 'md' => 'size-11 text-base', 'lg' => 'size-16 text-2xl', 'xl' => 'size-24 text-3xl'];
    $url = $user->avatarUrl();
    $show = $url && (! $public || $user->show_avatar);
    $initial = mb_strtoupper(mb_substr($user->name ?? '?', 0, 1));
@endphp

@if ($show)
    <img src="{{ $url }}" alt="" {{ $attributes->class(['shrink-0 rounded-full object-cover', $sizes[$size] ?? $sizes['md']]) }}>
@else
    <span {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-full bg-brand-600 font-semibold text-white', $sizes[$size] ?? $sizes['md']]) }} aria-hidden="true">{{ $initial }}</span>
@endif
