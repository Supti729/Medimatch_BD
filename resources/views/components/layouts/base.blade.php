{{--
    HTML shell shared by every layout: <head>, fonts, CSS/JS and the "skip to content" link.
    Pages should use <x-layouts.public>, <x-layouts.app>, <x-layouts.admin> or <x-layouts.auth>.
--}}
@props(['title' => null, 'description' => null, 'noindex' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#167d6f">
        @if ($noindex)
            <meta name="robots" content="noindex, nofollow">
        @endif
        <meta name="description" content="{{ $description ?? 'Medimatch BD helps people in Bangladesh find eligible medicines and connect with donors near them. Academic prototype.' }}">

        <title>{{ $title ? $title.' · '.config('app.name') : config('app.name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body {{ $attributes->class(['min-h-screen']) }}>
        <a href="#main"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-control focus:bg-brand-700 focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-white">
            Skip to main content
        </a>

        {{ $slot }}
    </body>
</html>
