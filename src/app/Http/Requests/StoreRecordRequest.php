<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recordId' => 'required|string|max:255',
            'time' => 'required|date_format:Y-m-d H:i:s|date',
            'sourceId' => 'required|string|max:255',
            'destinationId' => 'required|string|max:255',
            'type' => 'required|string|in:positive,negative',
            'value' => 'required|numeric|decimal:0,2',
            'unit' => 'required|string|max:255',
            'reference' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'recordId.required' => 'The recordId is required.',
            'recordId.string' => 'The recordId must be a string.',
            'time.required' => 'The time is required.',
            'time.date_format' => 'The time must be in format Y-m-d H:i:s.',
            'type.in' => 'The type must be either "positive" or "negative".',
            'value.numeric' => 'The value must be a valid number.',
            'value.decimal' => 'The value must have at most 2 decimal places.',
        ];
    }
}
