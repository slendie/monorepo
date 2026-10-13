# Desempenho

Números **informativos** para dar uma ordem de grandeza — não são uma garantia nem fazem parte do CI. Medem o custo
da framework por pedido, sem servidor HTTP, sem rede e sem I/O de base de dados.

## Como medir

```bash
php -d xdebug.mode=off framework/tests/bench/requests.php        # 1000 pedidos
php -d xdebug.mode=off framework/tests/bench/requests.php 5000   # outro número de pedidos
```

O script (`framework/tests/bench/requests.php`) arranca a aplicação de fixtures (`framework/tests/_data/testing`), faz 20 pedidos
de aquecimento (compila a view e carrega as classes) e mede `GET /` — uma view Blade simples com um `@foreach` — através
do `Kernel` completo: middleware global, grupo `web` (sessão em memória, CSRF, `$errors`), router, controller/view e a
preparação da resposta. A pasta `bench/` não é um *testsuite* do PHPUnit.

## Resultados

| Condições | req/s | ms/pedido | Memória |
|---|---:|---:|---|
| PHP 8.5.4 CLI · Windows 11 · SQLite em memória · sem opcache · Xdebug desligado | ≈ 850 | ≈ 1,2 | +0,7 MB em 1000 pedidos (pico 5,1 MB) |
| idem, com `opcache.enable_cli=1` | ≈ 970 | ≈ 1,0 | pico 2,0 MB |
| idem, com a extensão Xdebug em modo `develop` (por omissão no Laragon) | ≈ 460 | ≈ 2,2 | — |

Notas:

- O Xdebug carregado, mesmo sem *debugger*, **abranda para metade**: meça sempre com `-d xdebug.mode=off`.
- O crescimento de memória vem do handler de sessão em memória (cada pedido sem cookie cria uma sessão nova); com o handler
  de ficheiros ou com a cookie reaproveitada fica estável.
- Em produção o PHP-FPM com opcache tem custos de arranque por pedido (autoload, `bootstrap/app.php`, ler `config/*.php`)
  que este benchmark, que reutiliza a aplicação, não inclui. Use-o para comparar versões da framework entre si, não para
  prever o tráfego que um servidor aguenta.
- Medições em Linux ficam por registar (ainda não há uma máquina de referência): acrescente uma linha à tabela com a
  mesma receita quando houver.
