<?php

/*
|--------------------------------------------------------------------------
| Navigation menus
|--------------------------------------------------------------------------
| One place that lists every menu link. Each item points to a route NAME.
| If the route does not exist yet, <x-nav.link> shows the item as "Soon"
| instead of a dead link, so menus are always honest about what works.
*/

return [

    // Top navigation on the public website
    'public' => [
        ['label' => 'Browse medicines', 'route' => 'medicines.index'],
        ['label' => 'How it works', 'route' => 'how-it-works'],
        ['label' => 'Safety', 'route' => 'safety'],
        ['label' => 'About', 'route' => 'about'],
        ['label' => 'Contact', 'route' => 'contact'],
    ],

    // Footer columns on the public website
    'footer' => [
        'Explore' => [
            ['label' => 'Browse medicines', 'route' => 'medicines.index'],
            ['label' => 'Donate medicine', 'route' => 'listings.create'],
            ['label' => 'Request medicine', 'route' => 'requests.create'],
        ],
        'Learn' => [
            ['label' => 'How it works', 'route' => 'how-it-works'],
            ['label' => 'Safety guidelines', 'route' => 'safety'],
            ['label' => 'About', 'route' => 'about'],
            ['label' => 'Contact', 'route' => 'contact'],
        ],
        'Legal' => [
            ['label' => 'Privacy policy', 'route' => 'privacy'],
            ['label' => 'Terms and conditions', 'route' => 'terms'],
        ],
    ],

    // Sidebar for signed-in users
    'app' => [
        'Overview' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Browse medicines', 'route' => 'medicines.index', 'icon' => 'magnifying-glass'],
        ],
        'My activity' => [
            ['label' => 'My listings', 'route' => 'listings.index', 'icon' => 'gift'],
            ['label' => 'My requests', 'route' => 'requests.index', 'icon' => 'clipboard-document-list'],
            ['label' => 'Saved medicines', 'route' => 'saved.index', 'icon' => 'bookmark'],
            ['label' => 'Messages', 'route' => 'messages.index', 'icon' => 'chat-bubble-left-right'],
            ['label' => 'Notifications', 'route' => 'notifications.index', 'icon' => 'bell'],
            ['label' => 'History', 'route' => 'history.index', 'icon' => 'clock'],
        ],
        'Account' => [
            ['label' => 'Profile', 'route' => 'profile.show', 'icon' => 'user-circle'],
            ['label' => 'Verification', 'route' => 'verification.index', 'icon' => 'shield-check'],
            ['label' => 'Privacy settings', 'route' => 'settings.privacy', 'icon' => 'eye-slash'],
            ['label' => 'Security settings', 'route' => 'settings.security', 'icon' => 'lock-closed'],
        ],
    ],

    // Bottom bar for signed-in users on phones (keep to five items)
    'app_mobile' => [
        ['label' => 'Home', 'route' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Browse', 'route' => 'medicines.index', 'icon' => 'magnifying-glass'],
        ['label' => 'Donate', 'route' => 'listings.create', 'icon' => 'plus-circle'],
        ['label' => 'Messages', 'route' => 'messages.index', 'icon' => 'chat-bubble-left-right'],
        ['label' => 'Profile', 'route' => 'profile.show', 'icon' => 'user-circle'],
    ],

    // Sidebar for administrators
    'admin' => [
        'Overview' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'chart-bar'],
        ],
        'Manage' => [
            ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users'],
            ['label' => 'Medicines', 'route' => 'admin.medicines.index', 'icon' => 'beaker'],
            ['label' => 'Listings', 'route' => 'admin.listings.index', 'icon' => 'gift'],
            ['label' => 'Requests', 'route' => 'admin.requests.index', 'icon' => 'clipboard-document-list'],
            ['label' => 'Partners', 'route' => 'admin.partners.index', 'icon' => 'building-office-2'],
        ],
        'Trust and safety' => [
            ['label' => 'Verification', 'route' => 'admin.verifications.index', 'icon' => 'document-check'],
            ['label' => 'Reports', 'route' => 'admin.reports.index', 'icon' => 'flag'],
            ['label' => 'Transactions', 'route' => 'admin.transactions.index', 'icon' => 'arrows-right-left'],
            ['label' => 'Audit logs', 'route' => 'admin.audit-logs.index', 'icon' => 'document-magnifying-glass'],
        ],
        'System' => [
            ['label' => 'Notifications', 'route' => 'admin.notifications.index', 'icon' => 'megaphone'],
            ['label' => 'Settings', 'route' => 'admin.settings', 'icon' => 'cog-6-tooth'],
        ],
    ],

];
