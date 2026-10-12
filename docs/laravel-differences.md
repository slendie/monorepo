# Diferenças para o Laravel

A Slendie copia o Laravel onde copiar é grátis (nomes, pastas, APIs) e diverge onde o Laravel é pesado para o caso
de uma landing page com painel. Se vens do Laravel, isto é o que muda.

## Igual (ou quase)
Estrutura de pastas, `routes/web.php` com `Route::`, controllers com DI e route-model binding, `Request`/`Response`,
middleware com `handle($request, $next)`, grupos `web`/`api`, CSRF, sessão com flash, Blade (subconjunto), `view()`,
`redirect()`, `back()`, `abort()`, `config()`, `env()`, `.env`, `Model` com `fillable/casts/timestamps/scopes/relações`,
Query Builder, migrações com `Schema`/`Blueprint`, seeders, `php slendie` (≈ artisan), Service Providers,
`vendor:publish`, descoberta de packages, Pest com `$this->get()->assertOk()`.

## Nomes diferentes
| Laravel | Slendie | Porquê |
|---|---|---|
| `php artisan` | `php slendie` | identidade |
| `$this->artisan()` em testes | `$this->console()` | idem |
| `Illuminate\…` | `Slendie\…` | |
| `->first()` / `->get()` | também `->fetch()` / `->fetchAll()` | pedido do brief; ambos funcionam |
| `Illuminate\Database\Eloquent\Model` | `Slendie\Model\Model` | |

## Não existe (e alternativa)
| Laravel | Slendie |
|---|---|
| Eager loading `with()` | `->load()` por model ou `whereIn` manual |
| `belongsToMany`, polimórficas | tabela pivot como model próprio |
| Factories / Faker | seeders com dados fixos; `fakerphp/faker` opcional |
| Observers, Events/Listeners, Queue, Jobs | hooks `static::creating()` no model; package futuro |
| Mail | package `slendie/mail` (planeado) ou PHPMailer directo |
| Cache (`Cache::`) | ficheiro próprio em `storage/framework/cache` ou package |
| Storage/Filesystem (`Storage::`) | `UploadedFile::store()` + funções nativas |
| Blade components `<x-…>`, slots | `@include` com dados |
| Policies/Gates `can()` | middleware próprio; package futuro |
| Rate limiting `throttle` | middleware próprio (login do `slendie/auth` traz throttle simples) |
| Broadcasting, Notifications, Scheduler, Horizon, Sanctum, Fortify, Breeze | fora de âmbito; `slendie/auth` cobre login + tokens |
| Auth no core | package `slendie/auth` |
| Config/route cache | não há (projectos pequenos; medir antes) |
| Mockery, fakes de serviços | `swap()` com classes anónimas |
| Localização completa | `__()` com ficheiros `lang/`, sem pluralização avançada |

## Filosofia
- **Zero dependências em runtime.** Só PHP ≥ 8.5 e PDO.
- **Core < 8 000 linhas.** Lê-se numa tarde.
- **Erros ruidosos.** Directiva Blade desconhecida, mass-assignment indevido ou operador SQL inválido lançam excepção em vez de falhar em silêncio.
- **Tudo é package.** Se o caso dos 90% (landing + formulário + painel) não precisa, não está no core.
