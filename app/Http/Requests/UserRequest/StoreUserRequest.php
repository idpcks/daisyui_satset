<?php

namespace App\Http\Requests\UserRequest;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
        $user = $this->route('user');

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
            'password' => [
                'nullable',
                'string',
                Password::default(),
                'confirmed',
            ],
            'role' => ['required', 'string', Rule::in(Role::assignableNamesFor($this->user()))],
            'is_active' => ['boolean'],
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
            'username.regex' => __('messages.validation.store_user.username_regex'),
            'username.unique' => __('messages.validation.store_user.username_unique'),
            'email.unique' => __('messages.validation.store_user.email_unique'),
            'password.confirmed' => __('messages.validation.store_user.password_confirmed'),
            'role.in' => __('messages.validation.store_user.role_in'),
        ];
    }
}
