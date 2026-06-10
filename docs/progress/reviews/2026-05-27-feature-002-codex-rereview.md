# Re-review Codex — Feature 002: Cadastro de Faturamento

- **Data:** 2026-05-27
- **Escopo:** nova rodada de testes + análise forte de regra de negócio, segurança e performance conforme `CLAUDE.md` e skills Claude (`backend`, `tester`, `frontend`)
- **Resultado:** os bloqueadores do review anterior foram corrigidos. A suíte está verde. Restam riscos médios/baixos antes de considerar a feature plenamente robusta.

## Achados

### 1. Validação roda antes da autorização nos Form Requests de invoice

**Severidade:** média — segurança/isolamento

`StoreInvoiceRequest` e `UpdateInvoiceRequest` usam `Rule::exists` escopado pela empresa da rota:

- `app/Http/Requests/Invoicing/StoreInvoiceRequest.php:15`
- `app/Http/Requests/Invoicing/UpdateInvoiceRequest.php:46`

Mas a autorização ainda está no controller:

- `app/Http/Controllers/Invoicing/InvoiceController.php:77`
- `app/Http/Controllers/Invoicing/InvoiceController.php:116`

Em Laravel, o `FormRequest` valida antes de entrar no método do controller. Para um usuário sem acesso à empresa, isso pode retornar `422` antes do `403` dependendo do payload, e no caso de `client_id` pode virar canal de inferência sobre existência de cliente dentro de uma empresa inacessível.

**Recomendação:** mover a autorização para `authorize()` nos Form Requests, usando a empresa da rota (`$this->user()->can('update', $this->route('company'))` para store, e checagem da invoice + policy para update). Manter a autorização no controller como redundância não machuca, mas a primeira barreira deve ser autorização.

### 2. Criação de lote usa transação no controller

**Severidade:** média — arquitetura/regra de negócio

A atomicidade do lote foi corrigida via `DB::transaction`, mas ela ficou no controller:

- `app/Http/Controllers/Invoicing/SimulationBatchController.php:56`

Pela skill `/backend`, controller deve só traduzir HTTP para use case; regra transacional da operação pertence à aplicação/infra, não à camada HTTP. Hoje, se o mesmo use case for usado por job/CLI/evento no futuro, ele volta a ficar não atômico.

**Recomendação:** mover a transação para um Application service/use case transacional ou para uma abstração de transaction manager injetada. O controller deve chamar `$create(...)` sem saber que a operação precisa ser atômica.

### 3. Filtro por competência usa função em coluna indexada

**Severidade:** média — performance

A listagem tem índice por `(company_id, data_emissao)`:

- `database/migrations/2026_05_27_000003_create_invoices_table.php:30`

Mas o filtro por competência usa `to_char(data_emissao, 'YYYY-MM')`:

- `app/src/Invoicing/Infrastructure/Persistence/EloquentInvoiceRepository.php:110`

Isso tende a impedir o uso eficiente do índice em `data_emissao` conforme a tabela cresce.

**Recomendação:** transformar `YYYY-MM` em intervalo e usar `whereBetween`/`>=` e `<`:

- início: primeiro dia do mês;
- fim exclusivo: primeiro dia do mês seguinte.

### 4. Listagens retornam arrays completos, sem paginação

**Severidade:** baixa/média — performance/UX

As listagens de invoices, clientes e lotes fazem `get()` e retornam tudo:

- `app/src/Invoicing/Infrastructure/Persistence/EloquentInvoiceRepository.php:121`
- `app/src/Invoicing/Infrastructure/Persistence/EloquentClientRepository.php:51`
- `app/src/Invoicing/Infrastructure/Persistence/EloquentSimulationBatchRepository.php:149`

Para clientes talvez seja aceitável no começo; para invoices, a tendência natural é crescer mês a mês e virar carga pesada no Inertia/JSON.

**Recomendação:** definir paginação no contrato antes de a UI depender de arrays completos. Para invoices, priorizar paginação + filtros persistidos.

### 5. Delete de cliente não é atômico

**Severidade:** baixa/média — consistência

O fluxo de excluir cliente executa soft delete e depois nulifica notas em chamadas separadas:

- `app/src/Invoicing/Application/UseCases/DeleteClientUseCase.php:21`
- `app/src/Invoicing/Application/UseCases/DeleteClientUseCase.php:22`

Se a segunda operação falhar, o cliente fica soft-deletado mas notas ainda apontam para ele. O FK `nullOnDelete` não ajuda aqui porque soft delete não dispara `ON DELETE`.

**Recomendação:** envolver o fluxo em transação no nível de aplicação/infra, do mesmo jeito conceitual que o lote.

## Regras de Negócio

Resolvido nesta rodada:

- `client_id` agora é validado por empresa e ignora clientes soft-deleted.
- `PUT/DELETE` individual de simulações agora retorna `422`.
- `tipo` foi adicionado ao contrato, DTO, controller, repository e feature tests.
- criação de lote está atômica no caminho HTTP.

Pendências:

- contrato/spec ainda não definem resposta para `tipo` inválido em query string; hoje qualquer valor vira filtro vazio.
- regras transacionais estão no HTTP em um caso e ausentes no delete de cliente.

## Segurança

Bom:

- Policies continuam filtrando por empresa/usuário.
- `client_id` não cruza mais empresas no create/update de invoice.
- simulações não podem ser alteradas/excluídas individualmente pela API.

Risco restante:

- autorização depois da validação em `FormRequest` pode produzir respostas diferentes (`422` vs `403`) para empresas inacessíveis.

## Performance

Bom:

- existe índice em `(company_id, data_emissao)`.
- existe unique parcial para simulações por empresa/tipo/mês.
- cache de simulações evita leitura repetida no filtro simples `is_simulation=true`.

Riscos:

- `to_char(data_emissao)` reduz a utilidade do índice.
- listagens sem paginação podem ficar caras no Inertia.
- cache de simulações é por empresa inteira; para empresas grandes, pode guardar payload amplo demais.

## Testes Executados

- `./vendor/bin/sail artisan test --testsuite=Feature` — passou: 106 testes, 282 assertions.
- `./vendor/bin/sail artisan test --testsuite=Unit` — passou: 68 testes, 116 assertions.
- `./vendor/bin/sail artisan test` — passou: 174 testes, 398 assertions.
- `./vendor/bin/sail npm run types:check` — passou sem erros.
- `./vendor/bin/sail composer lint` — passou, mas executou Pint em modo fix e adicionou newline final em testes antigos fora da feature.

## Observações de Worktree

O `composer lint` alterou apenas EOF/newline nestes arquivos fora da feature:

- `tests/Feature/Auth/*`
- `tests/Feature/Settings/*`
- `tests/Feature/DashboardTest.php`
- `tests/Feature/ExampleTest.php`
- `tests/Unit/ExampleTest.php`

Não reverti automaticamente para não desfazer mudanças sem sua decisão explícita.

## Conclusão

Eu consideraria os bloqueadores originais fechados. Para produção com mais confiança, eu ainda corrigiria autorização antes de validação e o filtro de competência por intervalo; os demais pontos podem entrar como hardening técnico antes da feature de fechamento de mês.

## Verificação Pós-correções

**Data:** 2026-05-27

Correções confirmadas:

- `StoreInvoiceRequest` e `UpdateInvoiceRequest` agora possuem `authorize()` antes da validação.
- Filtro de competência em invoices agora usa intervalo por data (`>= início` e `< próximo mês`), preservando melhor o índice em `data_emissao`.
- Invoices e clientes agora retornam paginação (`data` + `meta`) no JSON e props paginadas no Inertia.
- Transações de criação de lote e delete de cliente foram movidas para use cases via `TransactionManager`.

Verificações:

- `./vendor/bin/sail artisan test` — passou: 174 testes, 398 assertions.
- `./vendor/bin/sail npm run types:check` — passou sem erros.

Residual baixo:

- O contrato ainda não documenta `per_page`, embora o backend aceite `10/25/50/100`.
- `GET /simulation-batches` permanece sem paginação; aceitável no curto prazo, mas vale revisitar se lotes crescerem muito.
- Os testes cobrem o novo shape via uso de `data`, mas ainda não assertam explicitamente `meta`.
