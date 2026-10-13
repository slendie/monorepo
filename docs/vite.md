# Vite e assets

O skeleton **não depende de Node**: CSS e JS vivem em `public/css/app.css` e `public/js/app.js` e são servidos
directamente. Usa Vite quando quiseres TypeScript, React, Tailwind ou *bundling*.

## Activar Vite num projecto

```bash
npm init -y
npm i -D vite laravel-vite-plugin
```

`vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: ['resources/views/**'] })],
});
```

O `laravel-vite-plugin` só precisa de dois contratos que a Slendie cumpre: o ficheiro `public/hot` em
desenvolvimento e `public/build/manifest.json` em produção.

No layout:

```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

```bash
npm run dev      # HMR; escreve public/hot
npm run build    # public/build/
```

Em desenvolvimento a directiva emite `<script type="module" src="http://localhost:5173/@vite/client">` e os entries;
em produção emite `<link rel="stylesheet">`, `<link rel="modulepreload">` e `<script type="module">` com *hashes* do
manifest. Sem manifest e sem `hot` → excepção com a instrução `npm run build`.

O helper `vite(['resources/js/app.js'])` faz o mesmo que `@vite`. O manifest é procurado em
`public/build/manifest.json` (e em `public/build/.vite/manifest.json`, o local por omissão do Vite ≥ 5).

## Layout do skeleton sem Node

O `layouts/app.blade.php` do skeleton só usa `@vite` se existir `public/build/manifest.json` ou `public/hot`; caso contrário
usa `public/css/app.css` e `public/js/app.js`. Basta `npm run build` (ou `npm run dev`) para passar a Vite sem editar nada.

## React

```blade
@viteReactRefresh
@vite('resources/js/app.tsx')
```

O package [`slendie/react-admin`](react-admin.md) já traz tudo configurado.

## API PHP

```php
use Slendie\Support\Vite;

app(Vite::class)->asset('resources/img/logo.svg');     // URL com hash
app(Vite::class)->isRunningHot();
app(Vite::class)->useBuildDirectory('bundle')->useCspNonce($nonce);   // nonce em todas as tags
app(Vite::class)->content('resources/css/app.css');                    // CSS inline (e-mails, páginas críticas)
app(Vite::class)->manifestHash();                                       // muda quando muda o build
app(Vite::class)->resolveAssetUrlUsing(fn ($path) => 'https://cdn.exemplo.pt/'.$path);   // CDN ou subpasta
```

## `.gitignore`

```
/public/build
/public/hot
/node_modules
```
