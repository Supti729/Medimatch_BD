<?php

namespace App\Http\Requests\Account;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = is_string($this->input('email')) ? trim($this->input('email')) : null;
        $phone = is_string($this->input('phone')) ? trim($this->input('phone')) : null;

        $this->merge([
            'email' => $email === '' || $email === null ? null : mb_strtolower($email),
            'phone' => $phone === '' || $phone === null ? null : (PhoneNumber::normalize($phone) ?? $phone),
        ]);
    }

    public function rules(): array
    {
        $id = $this->user()->id;

        return [
            'email' => ['nullable', 'required_without:phone', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'phone' => ['nullable', 'required_without:email', 'regex:/^01[3-9]\d{8}$/', Rule::unique('users', 'phone')->ignore($id)],
            'confirm_password' => ['required', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Keep at least an email address or a phone number.',
            'phone.required_without' => 'Keep at least a phone number or an email address.',
            'email.unique' => 'Another account already uses this email address.',
            'phone.unique' => 'Another account already uses this phone number.',
            'phone.regex' => 'Enter a valid Bangladeshi mobile number, for example 01712345678.',
            'confirm_password.current_password' => 'Your password is not correct.',
        ];
    }
}
