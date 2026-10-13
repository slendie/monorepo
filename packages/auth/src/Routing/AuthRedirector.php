<?php

declare(strict_types=1);

namespace Slendie\Auth\Routing;

use Slendie\Http\RedirectResponse;
use Slendie\Http\Request;
use Slendie\Routing\Redirector;

/**
 * O Redirector da framework com `intended()`: depois do login, volta ao URL que o visitante queria (`url.intended`,
 * guardado pelo middleware `auth`). Substitui o binding `redirect`; `redirect()->intended('/admin')`.
 */
class AuthRedirector extends Redirector
{
    public function intended(string $default = '/', int $status = 302): RedirectResponse
    {
        $request = $this->app->bound('request') ? $this->app->make('request') : null;
        $intended = $request instanceof Request && $request->hasSession() ? $request->session()->pull('url.intended') : null;

        if (is_string($intended) && $intended !== '') {
            return $this->createRedirect($intended, $status);
        }

        return $this->to($default, $status);
    }
}
