# Traduções

A framework traz um tradutor mínimo, usado pela validação e disponível para a tua aplicação.

## Configuração

```php
// config/app.php
'locale' => env('APP_LOCALE', 'pt'),
'fallback_locale' => 'en',      // usado quando a chave não existe no locale activo
```

## Ficheiros

`lang/{locale}/{grupo}.php` devolve um array; a chave é `grupo.caminho.da.linha`.

```php
// lang/pt/messages.php
return [
    'welcome' => 'Olá, :name!',
    'cart'    => '{0} Carrinho vazio|{1} Um item|[2,*] :count itens',
];
```

```php
__('messages.welcome', ['name' => 'Ana']);     // "Olá, Ana!"
trans('messages.welcome', ['name' => 'Ana'], 'en');
trans_choice('messages.cart', 3);              // "3 itens"
```

- `:name`, `:Name` e `:NAME` substituem o valor tal como está, com a 1.ª letra em maiúscula ou todo em maiúsculas.
- Se a chave não existir em lado nenhum, o resultado é a própria chave.
- `trans_choice` aceita intervalos (`{0}`, `{1}`, `[2,*]`) ou o formato simples `singular|plural`; `:count` é preenchido.
- `lang_path('pt')` devolve o directório `lang/` do projecto.

## Textos da framework e como os sobrepor

A framework fornece `validation.php` e `pagination.php` em `pt` e `en`. Os ficheiros do projecto **sobrepõem** os da
framework linha a linha: basta declarar o que queres alterar.

```php
// lang/pt/validation.php
return [
    'required'   => 'Preencha o campo :attribute.',
    'attributes' => ['phone' => 'telemóvel'],
    'custom'     => ['email' => ['unique' => 'Este e-mail já está registado.']],
];
```

Para partir de uma cópia completa: `php slendie vendor:publish --tag=slendie-lang`.

## Em código

```php
$translator = app('translator');                 // Slendie\Validation\Translator
$translator->setLocale('en');
$translator->addLines(['messages.extra' => 'Extra'], 'pt');   // linhas em runtime
$translator->addNamespace('blog', __DIR__.'/../lang');        // __('blog::messages.hi') (packages)
$translator->addPath('/outro/lang');                          // directório extra, abaixo do do projecto
```

Os packages registam os seus textos com `addNamespace()`; o projecto pode sobrepô-los em `lang/vendor/{package}/{locale}/`.
