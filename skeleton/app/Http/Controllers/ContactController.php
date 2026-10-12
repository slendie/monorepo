<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Slendie\Http\RedirectResponse;
use Slendie\Http\Request;
use Slendie\Support\Facades\DB;

class ContactController
{
    /**
     * Recebe o formulário de contacto. Se a validação falhar, a framework volta ao formulário
     * com `$errors` e `old()`; o campo `website` é um honeypot (os humanos não o vêem nem o preenchem).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:30',
            'message' => 'nullable|string|max:2000',
            'website' => 'prohibited',
        ]);

        $now = date('Y-m-d H:i:s');

        DB::table('leads')->insert([
            ...array_intersect_key($data, array_flip(['name', 'email', 'phone', 'message'])),
            'source' => 'contact',
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return back()->with('status', 'Obrigado! Entraremos em contacto em breve.');
    }
}
