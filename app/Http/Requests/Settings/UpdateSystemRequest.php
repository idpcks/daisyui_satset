<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('system.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:60'],
            'locale' => ['required', 'string', Rule::in(array_keys((array) config('system.locales')))],
            'timezone' => ['required', 'string', 'timezone'],
            'maintenance_enabled' => ['required', 'boolean'],
            'maintenance_message' => [
                'nullable',
                'string',
                'max:500',
                'required_if:maintenance_enabled,true',
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
            'app_name.required' => __('messages.validation.system.app_name_required'),
            'app_name.max' => __('messages.validation.system.app_name_max'),
            'locale.in' => __('messages.validation.system.locale_in'),
            'timezone.timezone' => __('messages.validation.system.timezone_invalid'),
            'maintenance_message.required_if' => __('messages.validation.system.maintenance_message_required_if'),
            'maintenance_message.max' => __('messages.validation.system.maintenance_message_max'),
        ];
    }
}
