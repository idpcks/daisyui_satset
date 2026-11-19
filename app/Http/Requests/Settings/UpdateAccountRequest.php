<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
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
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')
                    ->whereNull('deleted_at')
                    ->ignore($user?->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->whereNull('deleted_at')
                    ->ignore($user?->id),
            ],
            // Perubahan email atau username bisa dipakai merebut akun, jadi
            // pemilik sesi harus membuktikan pengetahuannya atas password.
            'current_password' => ['required', 'string', 'current_password:web'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, strip, dan garis bawah.',
            'username.unique' => 'Username sudah dipakai pengguna lain.',
            'email.unique' => 'Email sudah dipakai pengguna lain.',
            'current_password.current_password' => 'Password saat ini tidak cocok.',
        ];
    }
}
