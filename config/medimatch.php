<?php

/*
|--------------------------------------------------------------------------
| Medimatch BD application settings
|--------------------------------------------------------------------------
| Fixed option lists shared by forms, filters and (later) validation rules.
*/

return [

    /*
    | Development-mode delivery of verification codes and password reset links.
    |
    | No SMS provider is configured, and email goes to the log file in development,
    | so codes cannot really reach the user. When this is on, the code or link is
    | shown on screen, clearly labelled as a development code.
    |
    | It can ONLY be on in the "local" and "testing" environments. Showing reset
    | links on screen in production would let anyone take over any account.
    */
    'dev_codes_on_screen' => (bool) env('MEDIMATCH_DEV_CODES_ON_SCREEN', false)
        && in_array(env('APP_ENV'), ['local', 'testing'], true),

    // Verification codes
    'verification_code_minutes' => 10,
    'verification_code_attempts' => 5,


    // key => label shown to users
    'listing_types' => [
        'donation' => 'Donation',
        'affordable_access' => 'Affordable access',
    ],

    'dosage_forms' => ['Tablet', 'Capsule', 'Syrup', 'Suspension', 'Sachet', 'Drops', 'Cream', 'Inhaler'],

    // Listing statuses that guests are allowed to see. Anything else is never returned by search.
    'public_listing_statuses' => ['available', 'requested', 'reserved'],

    'sort_options' => [
        'newest' => 'Newest first',
        'expiry' => 'Longest shelf life',
        'name' => 'Name (A to Z)',
    ],

    // Shown on the About page. From the project proposal.
    'team' => [
        ['name' => 'Supti Saha', 'role' => 'Team leader', 'focus' => 'Requirements, UI/UX, frontend and documentation'],
        ['name' => 'Atiar Rahman Rahat', 'role' => 'Backend and database', 'focus' => 'Laravel, MySQL, authentication, listings, requests and search'],
        ['name' => 'Emamul Muttakin', 'role' => 'AI and supporting modules', 'focus' => 'Semantic matching, verification, admin tools and security testing'],
    ],

];
