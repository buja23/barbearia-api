<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barber_id'    => 'required|integer|exists:barbers,id',
            'service_id'   => 'required|integer|exists:services,id',
            'scheduled_at' => [
                'required',
                'date_format:Y-m-d H:i:s',
                'after:now',
                'before:+1 year',
            ],
            'client_phone' => [
                'nullable',
                'regex:/^\+?[\d\s\-\(\)]{10,20}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.after'  => 'A data/hora deve ser no futuro.',
            'scheduled_at.before' => 'Não é permitido agendar com mais de 1 ano de antecedência.',
            'client_phone.regex'  => 'Formato de telefone inválido.',
        ];
    }
}
