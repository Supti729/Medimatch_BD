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


    // Listing rules checked by the server when a listing is saved
    'listing_rules' => [
        'min_shelf_life_months' => 3,   // expiry must be at least this far away
        'max_quantity' => 1000,
        'max_photos' => 4,
        'max_photo_kb' => 2048,
    ],

    // Open requests expire after this many days without a handover (see the requests:expire command).
    'request_expiry_days' => 60,

    // Affordable-access listings by verified partners. Off until partner licensing and the legal basis are confirmed.
    'allow_affordable_access' => false,

    // Units offered on the listing form
    'units' => ['tablets', 'capsules', 'bottles', 'sachets', 'strips', 'tubes', 'inhalers', 'drops bottles'],

    // Packaging conditions that may be listed. Opened or loose medicines are not allowed.
    'packaging' => [
        'sealed_box' => 'Sealed, in the original box',
        'sealed_strip' => 'Sealed strip or bottle, without the outer box',
    ],

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
