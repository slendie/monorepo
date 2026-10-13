# Changelog

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt/1.1.0/); versionamento [SemVer](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Added
- Landing page (hero, benefícios, formulário de contacto), página de obrigado, privacidade, termos e 404/500 com o layout do site.
- Formulário de contacto com `StoreLeadRequest` (validação, honeypot `website`, consentimento RGPD), modelo `Lead` e envio por `fetch` com fallback.
- API JSON `/api/leads` (listar, ver, actualizar estado, apagar) protegida por token Bearer (`API_TOKEN`).
- `AGENTS.md`, testes Pest, PHPStan nível 6 e Pint.
