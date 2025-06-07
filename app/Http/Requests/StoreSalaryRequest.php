<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSalaryRequest extends FormRequest
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
            'base_salary' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'bonus' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'deductions' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'tax_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2030'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'base_salary.required' => 'Lo stipendio base è obbligatorio.',
            'base_salary.numeric' => 'Lo stipendio base deve essere un numero.',
            'base_salary.min' => 'Lo stipendio base deve essere almeno 0.',
            'month.required' => 'Il mese è obbligatorio.',
            'month.between' => 'Il mese deve essere compreso tra 1 e 12.',
            'year.required' => 'L\'anno è obbligatorio.',
            'year.between' => 'L\'anno deve essere compreso tra 2020 e 2030.',
        ];
    }
}
