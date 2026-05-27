# Re-review Codex — Feature 001: Empresa, Sócios e Pró-labore

- **Data:** 2026-05-26
- **Feature:** 001 — Empresa, Sócios e Pró-labore
- **Context:** `Companies`
- **Revisor:** Codex
- **Tipo:** re-review após correções dos pontos abertos

## Resultado

Não encontrei novos bloqueios técnicos nos pontos corrigidos.

## Checklist revisado

- [x] Update de empresa valida CNPJ duplicado por usuário e retorna 422.
- [x] Update de recibo valida duplicidade por empresa, sócio e competência e retorna 422.
- [x] Rotas aninhadas de config validam pertencimento ao `{company}` da URL.
- [x] Rotas aninhadas de recibo validam pertencimento ao `{company}` da URL.
- [x] Feature tests cobrem CNPJ duplicado no update.
- [x] Feature tests cobrem competência duplicada no update de recibo.
- [x] Feature tests cobrem update/delete de config usando empresa errada.
- [x] Feature tests cobrem update/delete de recibo usando empresa errada.
- [x] Testes da feature passaram via Sail.
- [x] Typecheck passou via Sail.
- [ ] Verificação visual/manual da UI no navegador segue pendente.

## Evidências de código

### CNPJ duplicado no update

`app/Http/Requests/Companies/UpdateCompanyRequest.php` agora:
- normaliza CNPJ em `prepareForValidation`;
- exige `size:14`;
- aplica `Rule::unique('companies', 'cnpj')` com escopo por `user_id`;
- ignora a própria empresa.

Teste relacionado:
- `tests/Feature/Companies/CompanyTest.php` — `rejeita cnpj duplicado ao atualizar empresa`.

### Competência duplicada no update de recibo

`app/Http/Requests/Companies/UpdateProlaboreRecordRequest.php` agora valida duplicidade por:
- `company_id` da rota;
- `partner_id` do recibo atual;
- `competencia`;
- `id != record.id`.

Teste relacionado:
- `tests/Feature/Companies/ProlaboreRecordTest.php` — `rejeita atualização para competência já existente do mesmo sócio`.

### Pertencimento em rotas aninhadas

`app/Http/Controllers/Companies/ProlaboreConfigController.php` e `app/Http/Controllers/Companies/ProlaboreRecordController.php` agora fazem `abort_if(..., 404)` quando o recurso filho não pertence à empresa da URL.

Testes relacionados:
- `tests/Feature/Companies/ProlaboreConfigTest.php` — update/delete de config de outra empresa retornam 404.
- `tests/Feature/Companies/ProlaboreRecordTest.php` — update/delete de recibo de outra empresa retornam 404.

## Verificações executadas

```text
./vendor/bin/sail exec laravel.test php artisan test tests/Feature/Companies tests/Unit/Companies
Resultado: 72 passed, 118 assertions.
```

```text
./vendor/bin/sail exec laravel.test npm run types:check
Resultado: passou sem erros.
```

## Observações restantes

- A pendência de verificação visual/manual da UI permanece correta no `STATUS.md`.
- O status atual está coerente com a fase `frontend`: backend e testes corrigidos, faltando validar a UI no navegador antes de `done`.
- Não rodei navegação visual neste re-review.

## Assinatura

Assinado: Codex, 2026-05-26.
