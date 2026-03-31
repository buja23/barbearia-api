<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(function (Builder $query) {
                    $query->where('is_active', true);
                }),
            ],
            // pix (padrão) ou card
            'payment_method' => ['sometimes', 'in:pix,card'],
            // token gerado pelo MP Bricks no frontend — obrigatório apenas para cartão
            'card_token'     => ['required_if:payment_method,card', 'string'],
            // parcelas — padrão 1 (à vista)
            'installments'   => ['sometimes', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.exists'       => 'O plano selecionado não existe ou está inativo.',
            'card_token.required_if' => 'O token do cartão é obrigatório para pagamento com cartão.',
            'payment_method.in'    => 'Forma de pagamento inválida. Use "pix" ou "card".',
        ];
    }
}
