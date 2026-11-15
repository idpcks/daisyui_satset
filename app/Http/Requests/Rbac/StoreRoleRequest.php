<?php

namespace App\Http\Requests\Rbac;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('role.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9._-]*$/',
                'unique:roles,name',
            ],
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
            'name.required' => 'Nama peran wajib diisi.',
            'name.max' => 'Nama peran maksimal 50 karakter.',
            'name.regex' => 'Nama peran hanya boleh berisi huruf kecil, angka, titik, garis bawah, atau strip, dan diawali huruf.',
            'name.unique' => 'Nama peran sudah dipakai.',
        ];
    }
}
