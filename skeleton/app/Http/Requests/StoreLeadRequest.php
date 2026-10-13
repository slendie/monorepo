<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Slendie\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:30',
            'message' => 'nullable|string|max:2000',
            'consent' => 'accepted',
            // Honeypot: escondido por CSS; só os robots o preenchem.
            'website' => 'prohibited',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Indique o seu nome.',
            'email.required' => 'Indique o seu email.',
            'email.email' => 'Indique um email válido.',
            'consent.accepted' => 'É necessário aceitar a política de privacidade.',
            'website.prohibited' => 'Pedido inválido.',
        ];
    }
}
