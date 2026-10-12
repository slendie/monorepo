# Slendie v4 — Análise crítica do projeto

> Estado: rascunho para validação · Data: 2026-10-10
> Fontes analisadas: brief do projeto; `lcloss/php-mvc-skeleton` v1.1.0 (código local em `../php-mvc-skeleton`);
> `slendie/slendie` v3.1.3 + `slendie/framework` v3.0.5 (GitHub, docs em slendie.lcloss.dev).

## 1. O que o brief pede, em uma frase

Um "Laravel Light": framework PHP MVC, instalável via `composer create-project`, com Model estilo Eloquent,
Blade-like para páginas públicas, React para o painel interno, rotas num ficheiro, middlewares e migrações,
extensível por packages. Caso de uso dominante (90%): landing page + painel de leads.

## 2. O que existe hoje (herança) e o que vale manter

| Peça | php-mvc-skeleton v1.1 | slendie/framework v3 | Veredicto para v4 |
|---|---|---|---|
| Router | array em `config/routes.php`, `{id}`, middlewares hard-coded (`auth`) | igual + `access:perm`, singleton, `hasRoute` | **Reescrever.** API fluente `Route::get()`, middlewares registados por alias, grupos, nomes. |
| Request | `$_GET/$_POST/json`, headers | igual | **Manter a ideia**, acrescentar `Response`, `validate()`, method spoofing (`_method` para PUT/DELETE). |
| Blade | interpretador por regex que avalia expressões à mão (`evaluateExpression`, parser de arrays, funções em whitelist) | igual, mais directivas | **Substituir por compilador.** Compilar `.blade.php` para PHP nativo em cache (como o Laravel). Elimina ~1500 linhas de parser frágil, dá `@php`, `@for`, `@elseif` de graça e é muito mais rápido. |
| Database | PDO estático, sqlite/mysql/pgsql | igual | **Manter** (3 drivers), mas instância injectável + `query($sql)->fetch()/fetchAll()`. |
| Model | estáticos `create/find/all/update/delete`, devolve arrays | + `where/orWhere/orderBy/limit` via `SQL` | **Reescrever.** Objectos hidratados, `fillable`, timestamps, `where()->fetch()`, `whereLike`, `save()`, relações mínimas. |
| Migrações | SQL cru por driver, `migrate.php` | `Migrator` com batches; `rollback` só apaga registos (não reverte schema) | **Reescrever.** Classes `up()/down()` com Schema builder mínimo que gera DDL por driver. |
| Middlewares | `WebMiddleware` guarda Request em estático; `Auth` faz `echo 'Unauthorized'` | igual | **Reescrever.** Pipeline `handle(Request, Closure $next): Response`. |
| Env/Config | próprio, sem deps | phpdotenv | **Próprio** (zero deps), `config('app.name')`. |
| Vite | — | `Vite.php` com manifest + dev server | **Manter e generalizar** para React no painel. |
| Testes | Pest | Pest | **Pest 3+**, com `TestCase` que arranca a app em memória. |
| Mail | PHPMailer | PHPMailer | **Sair do core**, virar package opcional. |
| AI context | `bin/ai-context.php`, `AGENTS.md` | — | **Manter** (diferenciador útil). |

## 3. Problemas estruturais das versões anteriores (porque é que a v4 começa do zero)

1. **Sem namespace no core (skeleton):** classes globais `Router`, `Blade`, `Database` carregadas por `require_once` em `public/index.php`. Impossível publicar como package reutilizável e impossível testar isoladamente.
2. **Estado global por todo o lado:** `WebMiddleware::$request`, `Database::$pdo`, `Router::getInstance()`. Dificulta testes e impede duas ligações a BD.
3. **Blade interpretado:** o motor tenta avaliar expressões PHP com regex. Qualquer expressão fora da whitelist falha silenciosamente (devolve `null`). Risco de segurança (chamada genérica via `function_exists`) e de correcção. É a maior dívida técnica herdada.
4. **Middlewares não são um pipeline:** o Router conhece os nomes `auth`/`access` e instancia-os à mão. Um package não consegue registar um middleware.
5. **Model devolve arrays:** `User::find(1)['name']`. Não há objectos, `save()`, `fillable`, nem protecção contra mass-assignment.
6. **Migrações não reversíveis:** `rollback()` só apaga linhas da tabela `migrations`.
7. **Acoplamento framework ↔ aplicação:** `slendie/framework` tem `Controllers/Middlewares/AuthMiddleware` que depende de `App\Models\User` da aplicação. A framework não pode depender de código da app.
8. **Sem Response:** controllers fazem `echo` e `header()` directamente. Não é testável e impede middlewares de alterar a resposta.
9. **Sem container / DI:** o site v3 anuncia "automatic dependency injection", mas o código só faz reflection dos parâmetros da rota.

## 4. Pontos do brief que merecem desafio

### 4.1 "Views internas em React" dentro de uma framework PHP "light"
- Introduz Node, Vite, build step e um segundo ecossistema de dependências num projeto cujo objectivo é ser leve. Para um painel de leads (tabela + filtro + detalhe) é provavelmente mais trabalho do que Blade + um pouco de JS.
- **Recomendação:** a framework não deve impor React. Deve fornecer (a) uma camada JSON limpa (`Response::json()`, rotas `api`, auth por sessão ou token) e (b) um helper `@vite()` mais um **package opcional** `slendie/react-admin` com o scaffold do painel (Vite + React + cliente fetch). O core fica agnóstico; quem quiser Alpine/HTMX/Blade puro no painel não paga o custo do React.

### 4.2 API do Model: `$clientes->where(...)->fetch()` vs `Cliente::where(...)->first()`
- O brief usa instância + `fetch()/fetchAll()` (nomes de PDO). Quem vem do Laravel espera estático + `first()/get()`.
- **Recomendação:** suportar ambos sem duplicar código: métodos estáticos encaminham para o query builder (`__callStatic`), e o builder expõe `fetch()`/`first()` e `fetchAll()`/`get()` como sinónimos. Documentar `fetch/fetchAll` como forma canónica da Slendie e `first/get` como atalho para laravelistas.

### 4.3 "Packages para expandir a framework"
- Só faz sentido se existir um **Service Provider** (registo de rotas, middlewares, directivas Blade, comandos de consola, migrações, publicação de assets). Esta peça torna tudo o resto extensível e tem de estar no núcleo desde o início.

### 4.4 Métodos HTTP
- Faltam **PATCH**, **OPTIONS** e **HEAD** (HEAD deve ser respondido automaticamente para rotas GET; OPTIONS importa para CORS na API do painel). Formulários HTML só enviam GET/POST, por isso é obrigatório **method spoofing** via campo `_method` (`@method('PUT')`), como no Laravel.

### 4.5 Um package ou dois?
- Laravel separa `laravel/framework` (código) de `laravel/laravel` (skeleton). A v3 já fazia isto (`slendie/framework` + `slendie/slendie`). O skeleton v1.1 juntava tudo.
- Dois packages = framework actualizável por `composer update` sem tocar no projeto; é o modelo correcto para "instalável via Packagist" + "packages de extensão". **Recomendação: dois packages**, desenvolvidos neste repositório como monorepo (`framework/` + `skeleton/`), publicados em separado.

### 4.6 O que não entra no core (para continuar "light")
Queue, eventos complexos, broadcasting, filesystem abstractions, ORM com relações profundas (many-to-many com pivot, eager loading com constraints), localização completa, cache multi-driver, Artisan completo. Cada um destes pode ser package.

## 5. Princípios de design para a v4

1. **Zero dependências em runtime no core** (só PHP ≥ 8.4 + PDO). Dev: Pest, PHPStan, Pint.
2. **Tudo com namespace `Slendie\`**, PSR-4, `declare(strict_types=1)`, tipos em tudo, `readonly` onde fizer sentido, enums para métodos HTTP.
3. **Container pequeno** (bind/singleton/make com autowiring por reflection). Permite DI em controllers, middlewares e providers com cerca de 150 linhas.
4. **Request → pipeline de middlewares → Controller → Response.** Nada escreve para o output excepto `Response::send()` no front controller.
5. **Blade compilado para PHP**, cache em `storage/framework/views`. Sem interpretador.
6. **Model = objecto hidratado + Query Builder fluente**, com parâmetros ligados sempre (nunca interpolação).
7. **Migrações com `up()/down()`** e Schema builder mínimo (`create`, `table`, `drop`, tipos comuns, index, foreign). SQL cru continua disponível.
8. **Service Providers** como único ponto de extensão (framework e packages usam o mesmo mecanismo).
9. **CLI `php slendie`** mínimo: `serve`, `migrate`, `migrate:rollback`, `migrate:status`, `make:*`, `route:list`, `view:clear`, `key:generate`. Comandos registáveis por providers.
10. **Testável de raiz:** `TestCase` com `$this->get('/')->assertOk()->assertSee('...')`, BD SQLite em memória por teste.
11. **Nomenclatura Laravel** sempre que não haja razão forte para divergir (`app/Http/Controllers`, `routes/web.php`, `resources/views`, `database/migrations`, `config/*.php`, `storage/`). Quem vem do Laravel não deve precisar de aprender nomes novos.

## 6. Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| Âmbito crescer até virar "Laravel Medium" | Perde o propósito | Lista explícita de fora-do-core (§4.6); cada feature nova tem de justificar o caso dos 90%. |
| Blade compilado divergir do Blade real | Confunde laravelistas | Suportar subconjunto bem documentado; directivas desconhecidas geram erro claro em compilação. |
| React no core | Peso, build obrigatório | Package opcional (§4.1). |
| Query builder gerar SQL inválido para um dos 3 drivers | Bugs em produção | Grammar por driver só para DDL e `LIMIT/OFFSET`; testes em SQLite e MySQL (Docker) no CI. |
| Dois packages atrasarem o arranque | Fricção inicial | Monorepo com `path` repository no `composer.json` do skeleton durante o desenvolvimento. |

## 7. Decisões que precisam do utilizador antes de fixar a arquitectura

1. **Painel React:** no core (skeleton traz Vite+React por defeito) ou package opcional `slendie/react-admin` (core só JSON + `@vite`)? Recomendação: opcional.
2. **Packaging:** dois packages (`slendie/framework` + `slendie/slendie` skeleton) ou um só? Recomendação: dois, monorepo.
3. **Nome e versão:** continuar `slendie/slendie` em v4.0.0 (substitui v3) e manter namespace `Slendie\`? Ou novo vendor?
4. **PHP mínimo:** 8.4 (como v3/skeleton) ou 8.3 para alojamentos partilhados mais conservadores?
5. **Auth no core:** o caso dos 90% precisa de login para o painel. Incluir no core um `Auth` mínimo (sessão, `Auth::attempt`, middleware `auth`, model `User`, migração) ou deixar para package `slendie/auth`? Recomendação: core.
6. **Língua da documentação:** português de Portugal (como v3) ou inglês (alcance Packagist)?
