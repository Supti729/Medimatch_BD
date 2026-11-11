{{--
    "Are you sure?" dialog that submits a form when confirmed.

    <x-button variant="danger" data-modal-open="cancel-listing">Cancel listing</x-button>

    <x-confirm-modal id="cancel-listing" title="Cancel this listing?" :action="route('listings.cancel', $listing)"
        method="PATCH" confirm="Yes, cancel listing" tone="danger">
        People will no longer be able to request this medicine.
    </x-confirm-modal>
--}}
@props(['id', 'title', 'action', 'method' => 'POST', 'confirm' => 'Confirm', 'cancel' => 'Go back', 'tone' => 'primary'])

<x-modal :id="$id" :title="$title" size="sm">
    {{ $slot }}

    <x-slot:footer>
        <x-button variant="secondary" data-modal-close>{{ $cancel }}</x-button>
        <form method="POST" action="{{ $action }}" data-loading class="contents">
            @csrf
            @if (strtoupper($method) !== 'POST')
                @method($method)
            @endif
            <x-button type="submit" :variant="$tone === 'danger' ? 'danger' : 'primary'" class="w-full sm:w-auto">{{ $confirm }}</x-button>
        </form>
    </x-slot:footer>
</x-modal>
