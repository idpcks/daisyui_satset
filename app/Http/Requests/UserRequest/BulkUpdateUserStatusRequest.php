<?php

namespace App\Http\Requests\UserRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateUserStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('user.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => [
                'uuid',
                'distinct',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'is_active' => ['required', 'boolean'],
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
            'ids.required' => 'Pilih minimal satu pengguna.',
            'ids.array' => 'Daftar pengguna tidak valid.',
            'ids.min' => 'Pilih minimal satu pengguna.',
            'ids.max' => 'Maksimal 100 pengguna dapat diubah sekaligus.',
            'ids.*.uuid' => 'ID pengguna tidak valid.',
            'ids.*.distinct' => 'Pengguna yang dipilih tidak boleh duplikat.',
            'ids.*.exists' => 'Pengguna yang dipilih tidak ditemukan.',
            'is_active.required' => 'Status pengguna wajib ditentukan.',
            'is_active.boolean' => 'Status pengguna tidak valid.',
        ];
    }
}
