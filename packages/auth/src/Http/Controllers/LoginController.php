<?php

declare(strict_types=1);

namespace Slendie\Auth\Http\Controllers;

use LogicException;
use Slendie\Auth\AuthManager;
use Slendie\Auth\Contracts\StatefulGuard;
use Slendie\Auth\Http\Requests\LoginRequest;
use Slendie\Auth\Routing\AuthRedirector;
use Slendie\Auth\Support\LoginThrottle;
use Slendie\Exceptions\HttpException;
use Slendie\Http\Controller;
use Slendie\Http\RedirectResponse;
use Slendie\Http\Response;

class LoginController extends Controller
{
    public function show(): Response
    {
        return $this->view('auth::login');
    }

    public function store(LoginRequest $request, AuthManager $auth, LoginThrottle $throttle, AuthRedirector $redirector): RedirectResponse
    {
        $key = $request->throttleKey();

        if ($throttle->tooManyAttempts($key)) {
            $seconds = max(1, $throttle->availableIn($key));

            throw new HttpException(429, (string) __('auth::auth.throttle', ['seconds' => $seconds]), null, ['Retry-After' => (string) $seconds]);
        }

        $guard = $auth->guard();

        if (! $guard instanceof StatefulGuard) {
            throw new LogicException('O guard por omissão do login tem de ser de sessão.');
        }

        if (! $guard->attempt($request->credentials(), $request->boolean('remember'))) {
            $throttle->hit($key);

            return back()
                ->withErrors(['email' => (string) __('auth::auth.failed')])
                ->withInput(['email' => $request->string('email')]);
        }

        $throttle->clear($key);

        $home = config('auth.home', '/admin');

        return $redirector->intended(is_string($home) && $home !== '' ? $home : '/admin');
    }
}
