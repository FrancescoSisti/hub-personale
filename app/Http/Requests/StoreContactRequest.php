<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public API endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email:rfc,dns|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|min:10|max:5000',
            'phone' => 'nullable|string|regex:/^[\+]?[0-9\s\-\(\)]{7,20}$/|max:20',
            'company' => 'nullable|string|max:255',
            'origin' => 'nullable|string|max:255|url',
            'extra_data' => 'nullable|array|max:10',
            'extra_data.*' => 'string|max:500',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Il nome è obbligatorio',
            'name.min' => 'Il nome deve essere di almeno 2 caratteri',
            'name.max' => 'Il nome non può superare i 255 caratteri',
            'email.required' => 'L\'email è obbligatoria',
            'email.email' => 'Inserisci un indirizzo email valido',
            'message.required' => 'Il messaggio è obbligatorio',
            'message.min' => 'Il messaggio deve essere di almeno 10 caratteri',
            'message.max' => 'Il messaggio non può superare i 5000 caratteri',
            'phone.regex' => 'Il numero di telefono non è in un formato valido',
            'origin.url' => 'L\'origine deve essere un URL valido',
            'extra_data.max' => 'Non puoi inviare più di 10 campi aggiuntivi',
            'extra_data.*.max' => 'Ogni campo aggiuntivo non può superare i 500 caratteri',
        ];
    }

    /**
     * Get custom attributes for error messages
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'email',
            'subject' => 'oggetto',
            'message' => 'messaggio',
            'phone' => 'telefono',
            'company' => 'azienda',
            'origin' => 'origine',
            'extra_data' => 'dati aggiuntivi',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'I dati forniti non sono validi',
                'errors' => $validator->errors(),
            ], 422)
        );
    }


}
