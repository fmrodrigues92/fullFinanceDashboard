# Review Codex — Feature 001: Empresa, Sócios e Pró-labore

- **Data:** 2026-05-26
- **Feature:** 001 — Empresa, Sócios e Pró-labore
- **Context:** `Companies`
- **Revisor:** Codex
- **Objetivo:** registrar os problemas encontrados no review para orientar correção pelo Claude, sem alterar o histórico do `docs/progress/STATUS.md`.

## Regra para o Claude

- [ ] Não atualizar fase/status histórico antes de corrigir os pontos técnicos abaixo.
- [ ] Usar este arquivo como roteiro de correção.
- [ ] Rodar comandos sempre via Sail, conforme `CLAUDE.md`.

## Checklist de correção

- [x] Corrigir validação de CNPJ no update de empresa para retornar 422, não 500.
- [x] Corrigir validação de duplicidade no update de recibo de pró-labore para retornar 422, não 500.
- [x] Garantir que `config` e `record` pertencem ao `{company}` da URL em rotas aninhadas.
- [x] Adicionar/ajustar feature tests cobrindo os três cenários acima.
- [x] Repetir typecheck via Sail — passou sem erros.
- [x] Verificar UI no navegador conforme skill `/frontend` — validado pelo usuário em 2026-05-26.
- [x] Depois das correções, atualizar o `STATUS.md` apenas por acréscimo ou avanço explícito de fase, sem apagar o log antigo.
- [x] Depois das correções, atualizar checklist/handoff da spec se o fluxo SDD exigir.

## Problemas técnicos encontrados

### 1. Update de empresa pode virar erro 500 ao duplicar CNPJ

**Arquivos envolvidos:**
- `app/Http/Requests/Companies/UpdateCompanyRequest.php`
- `app/Http/Controllers/Companies/CompanyController.php`
- `app/src/Companies/Infrastructure/Persistence/EloquentCompanyRepository.php`
- `database/migrations/2026_05_25_000001_create_companies_table.php`

**Problema:**
`UpdateCompanyRequest` valida `cnpj` apenas como `required|string`. O banco tem `unique(user_id, cnpj)`. Se o usuário editar uma empresa para usar o CNPJ de outra empresa do mesmo usuário, a request passa e o `update()` estoura `QueryException`, resultando em 500.

**Correção esperada:**
- Normalizar o CNPJ no update como já acontece no store.
- Validar `size:14`.
- Aplicar `Rule::unique('companies', 'cnpj')->where('user_id', $this->user()->id)->ignore($company->id)`.
- Manter erro 422 no campo `cnpj`.

**Teste esperado:**
- Usuário possui duas empresas.
- Faz `PUT /companies/{companyA}` com CNPJ da `companyB`.
- Resposta esperada: 422, não 500.

### 2. Update de recibo pode virar erro 500 ao conflitar competência

**Arquivos envolvidos:**
- `app/Http/Requests/Companies/UpdateProlaboreRecordRequest.php`
- `app/Http/Controllers/Companies/ProlaboreRecordController.php`
- `app/src/Companies/Infrastructure/Persistence/EloquentProlaboreRecordRepository.php`
- `database/migrations/2026_05_25_000004_create_prolabore_records_table.php`

**Problema:**
`UpdateProlaboreRecordRequest` não valida unicidade de `company_id`, `partner_id` e `competencia`. O banco tem `unique(company_id, partner_id, competencia)`. Ao editar um recibo para uma competência já usada pelo mesmo sócio na mesma empresa, a request passa e o banco pode gerar erro 500.

**Correção esperada:**
- Adicionar validação no update ignorando o próprio recibo.
- Garantir que o escopo use a empresa da rota e o sócio do recibo atual.
- Responder 422 com mensagem de duplicidade.

**Teste esperado:**
- Criar dois recibos para o mesmo sócio em competências diferentes.
- Fazer `PUT` em um deles alterando `competencia` para a competência do outro.
- Resposta esperada: 422, não 500.

### 3. Rotas aninhadas não garantem pertencimento do filho ao `{company}` da URL

**Arquivos envolvidos:**
- `app/Http/Controllers/Companies/ProlaboreConfigController.php`
- `app/Http/Controllers/Companies/ProlaboreRecordController.php`
- `routes/web.php`

**Problema:**
Em update/delete de configs e recibos, a autorização é feita no recurso filho (`config` ou `record`), mas não há garantia explícita de que esse filho pertence ao `{company}` recebido na URL. Se um usuário montar uma URL com empresa A e config/record da empresa B, a operação pode cair em erro interno ou responder sucesso sem efeito.

**Correção esperada:**
- Antes de update/delete, garantir que `config.company_id === company.id` e `record.company_id === company.id`.
- Preferir 404 quando o recurso não pertence à empresa da rota.
- Alternativa: usar scoped bindings/relacionamentos para as rotas aninhadas.

**Testes esperados:**
- Usuário tem duas empresas.
- Config/record pertence à empresa B.
- Tentar update/delete usando URL da empresa A com id do recurso da empresa B.
- Resposta esperada: 404 ou 403 consistente, nunca 200 silencioso ou 500.

## Pendências de conformidade SDD/skills

### 1. `STATUS.md` está como documento histórico

O arquivo `docs/progress/STATUS.md` não deve ser reescrito para apagar texto antigo. Quando houver correção ou avanço, adicionar nova linha/seção ou atualizar fase somente se isso fizer parte explícita do fluxo.

### 2. Status atual não reflete todo o trabalho implementado

Texto observado em `docs/progress/STATUS.md`:

```md
| 001 — Empresa, Sócios e Pró-labore | Companies | `spec` | `docs/specs/001-companies.md` | — | Aprovada em 2026-05-25; pronta para /backend |
```

Esse texto indica fase `spec` e pronta para `/backend`, apesar de já existirem backend, testes e frontend. Não mudar agora apenas para "arrumar o status"; primeiro corrigir os bugs técnicos e depois registrar o avanço de fase de forma intencional.

### 3. Handoff da spec ainda aparece incompleto

Texto observado em `docs/specs/001-companies.md`:

```md
## 10. Handoff

- [x] Spec aprovada pelo usuário
- [ ] `docs/progress/STATUS.md` atualizado
- [ ] Pronta para `/backend`
```

Depois das correções, avaliar se esse checklist deve ser atualizado. Se atualizar, registrar como avanço do fluxo, não como reescrita silenciosa de histórico.

### 4. Verificação frontend incompleta

A skill `/frontend` pede verificação no navegador do caminho feliz e dos erros. Até o review, houve typecheck, mas não foi registrado teste visual/manual.

**Correção esperada:**
- Rodar o servidor se necessário.
- Verificar tela de empresas, detalhes, sócios, configs e recibos.
- Testar pelo menos um erro 422 visível na UI.
- Registrar resultado neste review ou no status como novo log.

### 5. Comandos devem usar Sail

Durante o review, foi executado `npm run types:check` direto. Isso viola o invariante do `CLAUDE.md`.

**Correção esperada:**

```bash
./vendor/bin/sail exec laravel.test php artisan test tests/Feature/Companies tests/Unit/Companies
./vendor/bin/sail npm run types:check
```

Se o segundo comando não for suportado pela configuração local do Sail, usar o equivalente do projeto via Sail e registrar a alternativa.

## Verificações já realizadas no review

```text
./vendor/bin/sail exec laravel.test php artisan test tests/Feature/Companies tests/Unit/Companies
Resultado: 66 passed.
```

```text
npm run types:check
Resultado: passou sem erros.
Observação: comando foi executado fora do Sail; repetir via Sail antes de fechar.
```

## Ordem sugerida para correção

1. Corrigir `UpdateCompanyRequest` e adicionar teste de CNPJ duplicado no update.
2. Corrigir `UpdateProlaboreRecordRequest` e adicionar teste de competência duplicada no update.
3. Corrigir pertencimento de rotas aninhadas para configs e recibos.
4. Adicionar testes de rota aninhada inconsistente.
5. Rodar testes via Sail.
6. Rodar typecheck via Sail.
7. Verificar UI no navegador.
8. Atualizar documentação/status somente após os pontos acima.

## Assinatura

Assinado: Codex, 2026-05-26.
