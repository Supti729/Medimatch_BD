<?php

namespace App\Http\Requests\Account;

use App\Enums\Role;
use App\Models\Medicine;
use App\Models\MedicineListing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

/**
 * Validation and eligibility rules for creating or editing a listing.
 *
 * Field rules check the shape of the input. The checks in after() apply the
 * medicine safety rules: catalogue eligibility, shelf life, listing type and photo count.
 */
class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ownership of an existing listing is checked by the policy in the controller.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'brand_name' => is_string($this->input('brand_name')) ? preg_replace('/\s+/', ' ', trim($this->input('brand_name'))) : $this->input('brand_name'),
            'batch_number' => is_string($this->input('batch_number')) && trim($this->input('batch_number')) !== '' ? strtoupper(trim($this->input('batch_number'))) : null,
            'type' => $this->input('type', 'donation'),
        ]);
    }

    public function rules(): array
    {
        $rules = config('medimatch.listing_rules');

        return [
            'medicine_id' => ['required', 'integer', Rule::exists('medicines', 'id')],
            'brand_name' => ['required', 'string', 'min:2', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$rules['max_quantity']],
            'unit' => ['required', Rule::in(config('medimatch.units'))],
            'expiry' => ['required', 'date_format:Y-m'],
            'batch_number' => ['nullable', 'string', 'max:50', 'regex:/^[A-Z0-9\-\/ ]+$/'],
            'packaging' => ['required', Rule::in(array_keys(config('medimatch.packaging')))],
            'type' => ['required', Rule::in(array_keys(config('medimatch.listing_types')))],
            'division_id' => ['required', 'integer', Rule::exists('divisions', 'id')],
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')->where('division_id', $this->integer('division_id'))],
            'upazila_id' => ['required', 'integer', Rule::exists('upazilas', 'id')->where('district_id', $this->integer('district_id'))],
            'images' => ['nullable', 'array', 'max:'.$rules['max_photos']],
            'images.*' => [File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max($rules['max_photo_kb'])],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'confirm_sealed' => ['accepted'],
            'confirm_expiry' => ['accepted'],
            'confirm_storage' => ['accepted'],
            'confirm_allowed' => ['accepted'],
            'action' => ['required', Rule::in(['draft', 'submit'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // 1. The medicine must be one that may be listed at all.
            $medicine = Medicine::find($this->integer('medicine_id'));
            if (! $medicine->isListable()) {
                $validator->errors()->add('medicine_id', "{$medicine->generic_name} cannot be listed: {$medicine->restriction_reason}.");
            }

            // 2. Enough shelf life left. An expiry month is valid until the end of that month.
            $months = config('medimatch.listing_rules.min_shelf_life_months');
            $expiresAt = Carbon::createFromFormat('!Y-m', $this->input('expiry'))->endOfMonth();
            if ($expiresAt->lt(now()->addMonths($months))) {
                $validator->errors()->add('expiry', "The medicine must have at least {$months} months left before it expires.");
            }

            // 3. Individuals may only donate. Affordable access is for verified partners, when switched on.
            if ($this->input('type') === 'affordable_access'
                && ! ($this->user()->role === Role::Partner && config('medimatch.allow_affordable_access'))) {
                $validator->errors()->add('type', 'Only authorized partner organizations can create affordable-access listings.');
            }

            // 4. Photo limit counts the photos already on the listing that are being kept.
            $listing = $this->route('listing');
            $kept = $listing instanceof MedicineListing
                ? $listing->images()->whereNotIn('id', $this->input('remove_images', []))->count()
                : 0;
            $max = config('medimatch.listing_rules.max_photos');
            if ($kept + count($this->file('images', [])) > $max) {
                $validator->errors()->add('images', "A listing can have at most {$max} photos.");
            }
        }];
    }

    public function messages(): array
    {
        return [
            'medicine_id.required' => 'Choose the medicine from the catalogue.',
            'expiry.date_format' => 'Enter the expiry month and year.',
            'batch_number.regex' => 'The batch number can only contain letters, numbers, dashes and slashes.',
            'district_id.exists' => 'Choose a district in the selected division.',
            'upazila_id.exists' => 'Choose an upazila in the selected district.',
            'confirm_sealed.accepted' => 'Confirm that the medicine is unopened and in its original packaging.',
            'confirm_expiry.accepted' => 'Confirm that the expiry date is correct.',
            'confirm_storage.accepted' => 'Confirm that the medicine has been stored correctly.',
            'confirm_allowed.accepted' => 'Confirm that this is not a restricted medicine.',
        ];
    }

    public function attributes(): array
    {
        return [
            'medicine_id' => 'medicine',
            'brand_name' => 'brand name',
            'division_id' => 'division',
            'district_id' => 'district',
            'upazila_id' => 'upazila',
            'images.*' => 'photo',
        ];
    }
}
