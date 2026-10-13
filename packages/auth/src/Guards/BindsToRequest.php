<?php

declare(strict_types=1);

namespace Slendie\Auth\Guards;

use Closure;
use Slendie\Http\Request;

/**
 * Os guards são singletons; o estado (utilizador, logout...) vale para um pedido. Se o pedido muda
 * (vários pedidos na mesma aplicação, p.ex. nos testes) o estado esquece-se — excepto se foi fixado com `setUser()`.
 */
trait BindsToRequest
{
    private ?Request $boundRequest = null;

    /** Fixado por `setUser()`: sobrevive à mudança de pedido. */
    private bool $pinned = false;

    /** @var Closure(): ?Request */
    private readonly Closure $requestResolver;

    private function request(): ?Request
    {
        return ($this->requestResolver)();
    }

    private function syncRequest(): ?Request
    {
        $request = $this->request();

        if ($request !== $this->boundRequest) {
            $this->boundRequest = $request;

            if (! $this->pinned) {
                $this->resetRequestState();
            }
        }

        return $request;
    }

    abstract private function resetRequestState(): void;
}
