<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ];

        // Saat pengguna wajib mengganti password (mis. akun baru atau reset
        // password admin), password saat ini tidak diketahui dan tidak
        // diperlukan.
        if ($this->user()?->must_change_password) {
            $rules['current_password'] = ['nullable', 'string'];
        } else {
            $rules['current_password'] = ['required', 'string', 'current_password:web'];
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => __('messages.validation.password.current_password_mismatch'),
            'password.confirmed' => __('messages.validation.password.confirmed'),
        ];
    }
}
