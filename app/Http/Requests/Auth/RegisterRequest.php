<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tidy the input before validating: lower-case email, normalised phone number.
     * An invalid phone number is kept as typed so the "regex" rule rejects it.
     */
    protected function prepareForValidation(): void
    {
        $email = is_string($this->input('email')) ? trim($this->input('email')) : $this->input('email');
        $phone = is_string($this->input('phone')) ? trim($this->input('phone')) : $this->input('phone');

        $this->merge([
            'name' => is_string($this->input('name')) ? preg_replace('/\s+/', ' ', trim($this->input('name'))) : $this->input('name'),
            'email' => $email === '' ? null : (is_string($email) ? mb_strtolower($email) : $email),
            'phone' => $phone === '' ? null : (is_string($phone) ? (PhoneNumber::normalize($phone) ?? $phone) : $phone),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['nullable', 'required_without:phone', 'string', 'email:rfc', 'max:150', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'required_without:email', 'string', 'regex:/^01[3-9]\d{8}$/', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'division_id' => ['required', 'integer', Rule::exists('divisions', 'id')],
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')->where('division_id', $this->integer('division_id'))],
            'upazila_id' => ['required', 'integer', Rule::exists('upazilas', 'id')->where('district_id', $this->integer('district_id'))],
            'avatar' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2 * 1024)],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Enter an email address or a phone number.',
            'phone.required_without' => 'Enter a phone number or an email address.',
            'email.unique' => 'An account with this email address already exists.',
            'phone.unique' => 'An account with this phone number already exists.',
            'phone.regex' => 'Enter a valid Bangladeshi mobile number, for example 01712345678.',
            'district_id.exists' => 'Choose a district in the selected division.',
            'upazila_id.exists' => 'Choose an upazila in the selected district.',
            'terms.accepted' => 'You need to agree to the terms and privacy policy.',
        ];
    }

    public function attributes(): array
    {
        return [
            'division_id' => 'division',
            'district_id' => 'district',
            'upazila_id' => 'upazila',
            'avatar' => 'profile picture',
        ];
    }
}
