{{--
    Status badge for listings, requests, verification and accounts.
    Usage: <x-status-badge status="pending_review" />

    The same status always gets the same wording and colour across the app.
    Unknown statuses fall back to a neutral badge with a readable label.
--}}
@props(['status'])

@php
    $map = [
        // Listings
        'draft' => ['Draft', 'neutral'],
        'pending_review' => ['Pending review', 'warning'],
        'available' => ['Available', 'success'],
        'requested' => ['Requested', 'info'],
        'reserved' => ['Reserved', 'info'],
        'paused' => ['Paused', 'neutral'],
        'unavailable' => ['Unavailable', 'neutral'],
        'completed' => ['Completed', 'brand'],
        'cancelled' => ['Cancelled', 'neutral'],
        'rejected' => ['Rejected', 'danger'],
        'blocked' => ['Blocked', 'danger'],

        // Requests
        'active' => ['Active', 'success'],
        'matching' => ['Matching', 'info'],
        'match_found' => ['Match found', 'brand'],
        'verification_pending' => ['Verification pending', 'warning'],
        'expired' => ['Expired', 'neutral'],

        // Verification
        'not_submitted' => ['Not submitted', 'neutral'],
        'submitted' => ['Submitted', 'info'],
        'under_review' => ['Under review', 'warning'],
        'verified' => ['Verified', 'success'],
        'reupload_required' => ['Re-upload required', 'warning'],

        // Accounts and reports
        'suspended' => ['Suspended', 'danger'],
        'open' => ['Open', 'warning'],
        'resolved' => ['Resolved', 'success'],
        'escalated' => ['Escalated', 'danger'],
    ];

    $key = strtolower((string) $status);
    [$label, $tone] = $map[$key] ?? [ucfirst(str_replace('_', ' ', $key)), 'neutral'];
@endphp

<x-badge :tone="$tone" dot {{ $attributes }}>{{ $label }}</x-badge>
