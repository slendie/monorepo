<?php

declare(strict_types=1);

namespace Slendie\Auth\Http\Requests;

use Slendie\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable'],
        ];
    }

    /**
     * @return array{email: string, password: string}
     */
    public function credentials(): array
    {
        return [
            'email' => $this->string('email'),
            'password' => $this->string('password'),
        ];
    }

    /**
     * Chave do limitador: o email tentado e o IP (um robô que descarte a cookie de sessão não foge ao limite).
     */
    public function throttleKey(): string
    {
        return mb_strtolower($this->string('email')).'|'.($this->ip() ?? 'unknown');
    }
}
