<?php

namespace App\Http\Requests\Account;

use App\Models\Medicine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MedicineRequestForm extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership of an existing request is checked by the policy in the controller
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('notes'))) {
            $notes = trim($this->input('notes'));
            $this->merge(['notes' => $notes === '' ? null : $notes]);
        }
    }

    public function rules(): array
    {
        return [
            'medicine_id' => ['required', 'integer', Rule::exists('medicines', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('medimatch.listing_rules.max_quantity')],
            'unit' => ['required', Rule::in(config('medimatch.units'))],
            'preference' => ['required', Rule::in(['either', 'donation', 'affordable_access'])],
            'notes' => ['nullable', 'string', 'max:500'],
            'division_id' => ['required', 'integer', Rule::exists('divisions', 'id')],
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')->where('division_id', $this->integer('division_id'))],
            'upazila_id' => ['required', 'integer', Rule::exists('upazilas', 'id')->where('district_id', $this->integer('district_id'))],
            'confirm' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->has('medicine_id')) {
                return;
            }

            $medicine = Medicine::find($this->integer('medicine_id'));
            if ($medicine && ! $medicine->isListable()) {
                $validator->errors()->add('medicine_id', "{$medicine->generic_name} cannot be shared on Medimatch BD: {$medicine->restriction_reason}. Please ask a pharmacist or hospital.");
            }
        }];
    }

    public function messages(): array
    {
        return [
            'medicine_id.required' => 'Choose the medicine from the catalogue.',
            'district_id.exists' => 'Choose a district in the selected division.',
            'upazila_id.exists' => 'Choose an upazila or thana in the selected district.',
            'confirm.accepted' => 'Please confirm that this request is for genuine need.',
        ];
    }

    public function attributes(): array
    {
        return ['medicine_id' => 'medicine', 'division_id' => 'division', 'district_id' => 'district', 'upazila_id' => 'upazila'];
    }
}
