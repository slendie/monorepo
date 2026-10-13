<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use Slendie\Http\JsonResponse;
use Slendie\Http\RedirectResponse;

class ContactController extends Controller
{
    /**
     * Grava o lead. Se a validação falhar, a framework volta ao formulário com `$errors` e `old()`
     * (ou responde 422 em JSON). O campo `website` é um honeypot: os humanos não o vêem nem o preenchem.
     */
    public function store(StoreLeadRequest $request): RedirectResponse|JsonResponse
    {
        $lead = Lead::create([
            ...$request->safe()->only('name', 'email', 'phone', 'message'),
            'source' => 'landing',
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Obrigado! Entraremos em contacto em breve.', 'id' => $lead->id], 201);
        }

        return redirect()->route('thanks')->with('success', 'Obrigado! Entraremos em contacto em breve.');
    }
}
