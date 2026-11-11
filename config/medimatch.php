<?php

/*
|--------------------------------------------------------------------------
| Medimatch BD application settings
|--------------------------------------------------------------------------
| Fixed option lists shared by forms, filters and (later) validation rules.
*/

return [

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
