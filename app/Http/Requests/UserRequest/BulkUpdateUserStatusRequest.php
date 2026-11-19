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
            'ids.required' => __('messages.validation.bulk_user_status.ids_required'),
            'ids.array' => __('messages.validation.bulk_user_status.ids_array'),
            'ids.min' => __('messages.validation.bulk_user_status.ids_min'),
            'ids.max' => __('messages.validation.bulk_user_status.ids_max'),
            'ids.*.uuid' => __('messages.validation.bulk_user_status.ids_uuid'),
            'ids.*.distinct' => __('messages.validation.bulk_user_status.ids_distinct'),
            'ids.*.exists' => __('messages.validation.bulk_user_status.ids_exists'),
            'is_active.required' => __('messages.validation.bulk_user_status.is_active_required'),
            'is_active.boolean' => __('messages.validation.bulk_user_status.is_active_boolean'),
        ];
    }
}
