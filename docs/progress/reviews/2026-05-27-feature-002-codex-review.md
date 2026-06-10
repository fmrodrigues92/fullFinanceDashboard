# Review Codex — Feature 002: Cadastro de Faturamento

- **Data:** 2026-05-27
- **Escopo:** regras de negócio, testes, backend DDD, contrato e frontend conforme skills Claude (`backend`, `tester`, `frontend`)
- **Resultado:** há cobertura ampla e a suíte está verde, mas encontrei violações de negócio/autorização que bloqueiam marcar a feature como `done` sem correção.

## Achados

### 1. Nota fiscal aceita `client_id` de outra empresa ou inexistente

**Severidade:** alta

`StoreInvoiceRequest` e `UpdateInvoiceRequest` validam `client_id` apenas como inteiro positivo, sem `exists` e sem escopo por `company_id`:

- `app/Http/Requests/Invoicing/StoreInvoiceRequest.php:15`
- `app/Http/Requests/Invoicing/UpdateInvoiceRequest.php:15`

Depois, o controller grava esse ID diretamente na nota da empresa da rota:

- `app/Http/Controllers/Invoicing/InvoiceController.php:210`
- `app/Http/Controllers/Invoicing/InvoiceController.php:249`

Impacto:

- um usuário pode criar nota em uma empresa usando cliente de outra empresa;
- se o cliente não existir, a validação tende a deixar a FK explodir como erro de banco em vez de retornar `422`;
- isso viola RN1/RN16 e quebra o isolamento por empresa.

Recomendação:

- validar `client_id` com regra escopada por empresa (`Rule::exists('clients', 'id')->where('company_id', $companyId)->whereNull('deleted_at')`);
- adicionar feature tests para cliente inexistente, cliente de outra empresa do mesmo usuário e cliente de empresa de outro usuário.

### 2. Endpoints de nota permitem update/delete individual de simulações

**Severidade:** alta

A spec diz que simulações não têm update/delete individual e só podem ser removidas ao excluir o lote:

- RN12/RN13 em `docs/specs/002-invoicing.md`

Mas `PUT /companies/{company}/invoices/{invoice}` e `DELETE /companies/{company}/invoices/{invoice}` aceitam qualquer invoice da empresa. Não há bloqueio por `is_simulation`:

- `app/Http/Controllers/Invoicing/InvoiceController.php:234`
- `app/Http/Controllers/Invoicing/InvoiceController.php:273`
- `app/Policies/Invoicing/InvoicePolicy.php:38`
- `app/Policies/Invoicing/InvoicePolicy.php:46`

No update, o caso de uso ainda reconstrói a entidade como nota real (`isSimulation: false`, `simulationBatchId: null`), permitindo transformar uma simulação em nota real:

- `app/src/Invoicing/Application/UseCases/UpdateInvoiceUseCase.php:67`

No delete, a simulação recebe soft delete, mas a listagem continua incluindo todas as simulações mesmo com `deleted_at`, porque o filtro usa `is_simulation = true OR deleted_at IS NULL`:

- `app/src/Invoicing/Infrastructure/Persistence/EloquentInvoiceRepository.php:100`

Impacto:

- violação direta de RN12/RN13;
- API permite mutar simulações fora do lote;
- delete individual de simulação retorna sucesso, mas ela pode continuar aparecendo na listagem.

Recomendação:

- abortar `PUT/DELETE invoices/{invoice}` com `404` ou `422/403` quando `is_simulation = true`;
- adicionar feature tests cobrindo update/delete individual de simulação;
- considerar policy específica que negue mutação quando a invoice é simulação.

### 3. Criação de lote não é transacional

**Severidade:** média

`CreateSimulationBatchUseCase` cria o batch e depois insere as simulações em outra chamada:

- `app/src/Invoicing/Application/UseCases/CreateSimulationBatchUseCase.php:40`
- `app/src/Invoicing/Application/UseCases/CreateSimulationBatchUseCase.php:54`

Se `insertMany` falhar por corrida na unique parcial, erro de banco, ou qualquer exceção depois do `save`, fica um `simulation_batch` órfão sem simulações. A checagem prévia de conflito é boa para UX, mas não substitui atomicidade.

Recomendação:

- envolver criação do batch + insertMany em transação;
- capturar violação da unique parcial e converter para `SimulationConflict`/`422` quando possível;
- adicionar teste de aplicação ou integração simulando falha no `insertMany`.

### 4. Divergência entre spec e contrato/implementação no filtro `tipo`

**Severidade:** média/baixa

A spec lista `tipo` como filtro de invoices:

- `docs/specs/002-invoicing.md:160`

Mas o contrato publicado não documenta `tipo`, o DTO não carrega o filtro, e o controller só lê `is_simulation`/`competencia`:

- `docs/contracts/002-invoicing.md`
- `app/src/Invoicing/Application/DTOs/ListInvoicesFilter.php:48`
- `app/Http/Controllers/Invoicing/InvoiceController.php:167`

Recomendação:

- implementar e testar `?tipo=nacional|internacional`, ou atualizar a spec removendo esse filtro do escopo aprovado.

### 5. Frontend define layout na página, contrariando a skill `/frontend`

**Severidade:** baixa

A skill frontend diz que o layout é aplicado globalmente pelo `app.tsx` e que a página não deve definir `Page.layout`. As três páginas novas definem layout próprio:

- `resources/js/pages/Invoicing/Clients/Index.tsx:402`
- `resources/js/pages/Invoicing/Invoices/Index.tsx:666`
- `resources/js/pages/Invoicing/SimulationBatches/Index.tsx:361`

Há precedente em páginas existentes, então isso parece uma divergência entre padrão atual do repo e a skill. Mesmo assim, pela regra pedida no review, fica como pendência de conformidade.

Recomendação:

- alinhar com a decisão do projeto: ou remover layouts por página e deixar só `app.tsx`, ou atualizar a skill/padrão para aceitar breadcrumbs por página.

## Testes

Pontos positivos:

- Feature tests cobrem boa parte dos CAs principais de clientes, invoices, lotes, filtros e isolamento básico.
- Unit tests cobrem `AnexoCnae`, regras de `Invoice::createReal/createSimulation`, geração de meses/conflitos no lote e orquestração de delete de cliente.
- O `/tester` seguiu bem a ideia de mockar repositories em use cases.

Lacunas importantes:

- falta teste para `client_id` inexistente e `client_id` de outra empresa;
- falta teste para impedir update/delete individual de simulação;
- falta teste de update de invoice real;
- falta teste para o filtro `tipo`, caso ele continue na spec;
- falta teste ou desenho transacional para falha parcial na criação do lote.

## Verificações Executadas

- `./vendor/bin/sail artisan test` — passou: 167 testes, 389 assertions.
- `./vendor/bin/sail npm run types:check` — passou sem erros.

Observação: os comandos precisaram ser executados fora do sandbox porque o Sail/Docker não estava disponível no sandbox.

## Conclusão

A feature está bem encaminhada e a suíte atual está verde, mas eu não aprovaria como `done` ainda. Os dois bloqueadores são o `client_id` sem escopo por empresa e a possibilidade de mutar/remover simulações pelos endpoints de nota real.

---

## Resolução — 2026-05-27

Todos os achados corrigidos. Suíte: **174 testes, 174 passando**.

### #1 — `client_id` sem escopo ✅ resolvido

`StoreInvoiceRequest` e `UpdateInvoiceRequest` agora validam com `Rule::exists('clients','id')->where('company_id', $company->id)->whereNull('deleted_at')`.
Testes adicionados: cliente inexistente → 422, cliente de outra empresa do mesmo usuário → 422, cliente de empresa de outro usuário → 422.

### #2 — Mutação de simulações ✅ resolvido

`InvoiceController::update()` e `destroy()` adicionaram `abort_if($invoice->is_simulation, 422)` antes de qualquer processamento.
Testes adicionados: PUT em simulação → 422, DELETE em simulação → 422.

### #3 — Criação de lote não-transacional ✅ resolvido

`SimulationBatchController::store()` agora envolve o use case em `DB::transaction()`, garantindo atomicidade entre `batchRepo->save()` e `invoiceRepository->insertMany()`.

### #4 — Filtro `tipo` ausente ✅ implementado

`ListInvoicesFilter` recebeu `?string $tipo`. `EloquentInvoiceRepository::queryForCompany()` aplica `->where('tipo', $filter->tipo)` quando preenchido. A condição de cache foi atualizada para excluir o caso com `tipo !== null`. Contrato atualizado com o novo parâmetro.
Testes adicionados: `?tipo=nacional` e `?tipo=internacional`.

### #5 — Layout por página ✅ alinhado

`SKILL.md` do `/frontend` corrigido para refletir o padrão real do repo: breadcrumbs via `.layout` estático na página, consumido pelo `app.tsx`.
