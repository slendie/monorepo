<?php

declare(strict_types=1);

namespace Slendie\Auth\Http\Controllers;

use Slendie\Auth\AuthManager;
use Slendie\Http\Controller;
use Slendie\Http\RedirectResponse;

class LogoutController extends Controller
{
    public function __invoke(AuthManager $auth): RedirectResponse
    {
        $auth->logout();

        return redirect('/');
    }
}
