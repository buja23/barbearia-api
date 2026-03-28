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
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.exists' => 'O plano selecionado não existe ou está inativo.',
        ];
    }
}
