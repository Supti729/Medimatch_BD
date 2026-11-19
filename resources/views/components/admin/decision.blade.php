{{--
    Decision form used on admin review pages (listing review, document review, report details).
    <x-admin.decision title="Moderation decision" route="admin.listings.decide" :param="$listing['id']"
        :options="['approve' => ['Approve', 'Make the listing visible.'], 'reject' => ['Reject', '…']]" />

    Every decision needs a written reason, which is stored in the audit log (backend part).
--}}
@props(['title', 'route', 'param' => null, 'options', 'noteLabel' => 'Reason', 'noteHint' => 'Recorded in the audit log and, where relevant, shown to the user.', 'submit' => 'Record decision', 'feature' => 'Recording a decision'])

@php $ready = Route::has($route); @endphp

<x-card :title="$title">
    @error('decision') <x-alert tone="danger" class="mb-5">{{ $message }}</x-alert> @enderror

    <form class="space-y-5" @if ($ready) method="POST" action="{{ route($route, $param) }}" data-loading @else onsubmit="event.preventDefault()" @endif>
        @csrf
        <fieldset>
            <legend class="text-sm font-medium">Decision <span class="text-danger-600" aria-hidden="true">*</span></legend>
            <div class="mt-2 space-y-2">
                @foreach ($options as $value => [$label, $description])
                    <label class="flex items-start gap-3 rounded-control border border-line p-3 has-checked:border-brand-300 has-checked:bg-brand-50">
                        <input type="radio" name="decision" value="{{ $value }}" required class="mt-0.5 size-4 accent-brand-600">
                        <span class="text-sm"><span class="block font-semibold">{{ $label }}</span><span class="text-ink-soft">{{ $description }}</span></span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <x-form.textarea name="note" :id="'note-'.Str::slug($title)" :label="$noteLabel" rows="3" required maxlength="1000" :hint="$noteHint" />

        <x-button type="submit" block :disabled="! $ready">{{ $submit }}</x-button>
    </form>
</x-card>
