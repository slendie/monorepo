<?php

declare(strict_types=1);

namespace Slendie\ReactAdmin\Http\Controllers;

use Slendie\Http\Request;
use Slendie\Http\Response;
use Slendie\ReactAdmin\Support\Leads;
use Slendie\ReactAdmin\Support\UserPayload;

/** Serve a única view do painel; o resto é a SPA sobre /api/admin/*. */
final class AdminController
{
    public function __invoke(Request $request): Response
    {
        return response()->view('react-admin::app', [
            'config' => [
                'user' => UserPayload::from($request->user()),
                'statuses' => Leads::STATUSES,
                'locale' => 'pt',
                'base' => '/admin',
                'api' => '/api/admin',
            ],
        ]);
    }
}
