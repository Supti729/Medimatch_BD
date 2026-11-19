<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // users can only ever update their own profile (no id in the URL)
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => preg_replace('/\s+/', ' ', trim($this->input('name')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'division_id' => ['required', 'integer', Rule::exists('divisions', 'id')],
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')->where('division_id', $this->integer('division_id'))],
            'upazila_id' => ['required', 'integer', Rule::exists('upazilas', 'id')->where('district_id', $this->integer('district_id'))],
            'avatar' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2 * 1024)],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['division_id' => 'division', 'district_id' => 'district', 'upazila_id' => 'upazila', 'avatar' => 'profile picture'];
    }

    public function messages(): array
    {
        return [
            'district_id.exists' => 'Choose a district in the selected division.',
            'upazila_id.exists' => 'Choose an upazila in the selected district.',
        ];
    }
}
