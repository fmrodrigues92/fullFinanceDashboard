# Auditoria — Feature 006: Recibo de Pró-labore Inline no Dashboard (2026-06-05)

**Escopo:** arquivos criados/modificados na feature 006.

| Arquivo | Status |
|---------|--------|
| `app/Http/Requests/Companies/StoreProlaboreRecordRequest.php` | modificado |
| `app/Http/Requests/Companies/UpdateProlaboreRecordRequest.php` | modificado |
| `app/Http/Controllers/Companies/ProlaboreRecordController.php` | modificado |
| `app/src/Companies/Domain/ProlaboreRecord.php` | modificado |
| `app/src/Companies/Application/UseCases/ProlaboreRecord/GetProlaboreDashboardUseCase.php` | modificado |
| `app/src/Companies/Infrastructure/Persistence/EloquentProlaboreRecordRepository.php` | modificado |

## Resumo executivo

| Severidade | Qtd |
|------------|-----|
| CRÍTICO    | 0   |
| ALTO       | 0   |
| MÉDIO      | 1   |
| BAIXO      | 1   |
| INFO       | 0   |

---

## Achados

### [SEC-01] Update aceita qualquer record histórico, não só do mês corrente · MÉDIO

**Arquivo:** `app/Http/Requests/Companies/UpdateProlaboreRecordRequest.php` (regras) /
`app/Http/Controllers/Companies/ProlaboreRecordController.php` linha 105

**Problema:** `UpdateProlaboreRecordRequest` valida que o **campo `competencia` enviado na requisição** é o
mês corrente, mas não verifica que o **record que está sendo editado** pertence ao mês corrente. Qualquer
`prolabore_record` de meses passados (cujo `id` o usuário conheça — por exemplo, via a listagem em
`/companies/{id}/prolabore-records`) pode ser alvo de um PUT com `competencia = date('Y-m')`. O resultado
é a competência e o valor do record histórico sendo sobrescritos para o mês atual, corrompendo dados
financeiros passados de forma silenciosa.

**Impacto:** corrupção de histórico financeiro (valor e competência de recibos passados alterados pelo
próprio usuário). Sem impacto entre usuários (Policy garante ownership), mas viola a invariante de
imutabilidade de competências fechadas.

**Correção recomendada:** verificar na `UpdateProlaboreRecordRequest` (ou no controller, antes do use case)
que a competência atual do record é o mês corrente:

```php
// Em UpdateProlaboreRecordRequest::rules(), após capturar $record:
$record = $this->route('record');

// Adicionar ao início das regras — rejeitar edição de records históricos
if ($record !== null && $record->competencia->format('Y-m') !== date('Y-m')) {
    // Retornar erro via after hook ou custom rule:
}
```

Alternativa mais limpa no controller, logo após o `abort_if`:

```php
// ProlaboreRecordController::update(), linha 106
abort_if((int) $record->company_id !== (int) $company->id, 404);
abort_if(substr((string) $record->competencia, 0, 7) !== date('Y-m'), 422);
$this->authorize('update', $record);
```

O `abort_if(..., 422)` retorna JSON `{ "message": "..." }` sob `Accept: application/json` e redireciona
`back()` no Inertia, compatível com o padrão existente.

---

### [SEC-02] Race condition em `store` produz HTTP 500 em vez de 422 · BAIXO

**Arquivo:** `app/Http/Controllers/Companies/ProlaboreRecordController.php` linhas 84–88

**Problema:** a verificação de duplicata em `StoreProlaboreRecordRequest` usa um `SELECT EXISTS` separado do
INSERT. Em dois requests simultâneos para o mesmo `(company_id, partner_id, competencia)`, ambos podem
passar a validação e seguir para o use case. O segundo insert colide com o unique constraint do banco e
lança `\Illuminate\Database\QueryException` — que **não** é capturada pelo `catch (\DomainException)` do
controller, resultando em HTTP 500.

O frontend desabilita o botão durante `processing`, o que reduz muito a probabilidade, mas o endpoint é
acessível via requisição direta.

**Correção recomendada:**

```php
// ProlaboreRecordController::store()
try {
    $record = $create(new CreateProlaboreRecordInput(...));
} catch (\DomainException $e) {
    return $request->expectsJson()
        ? response()->json(['message' => $e->getMessage()], 422)
        : redirect()->back()->withErrors(['partner_id' => $e->getMessage()]);
} catch (\Illuminate\Database\QueryException $e) {
    // unique constraint por race condition
    $message = 'Já existe um recibo para este sócio nesta competência.';
    return $request->expectsJson()
        ? response()->json(['message' => $message], 422)
        : redirect()->back()->withErrors(['competencia' => $message]);
}
```

---

## Correções para o /backend
> Seção adicionada após aprovação do operador em 2026-06-05.

- **[SEC-01]** `app/Http/Controllers/Companies/ProlaboreRecordController.php:105` — o update não verifica se o record sendo editado pertence ao mês corrente. Adicionar logo após o `abort_if` de ownership:
  ```php
  abort_if(substr((string) $record->competencia, 0, 7) !== date('Y-m'), 422);
  ```
  Isso rejeita com 422 qualquer tentativa de editar um record histórico via este endpoint.

- **[SEC-02]** `app/Http/Controllers/Companies/ProlaboreRecordController.php:84` — o `catch (\DomainException)` não captura `\Illuminate\Database\QueryException` (unique constraint por race condition). Adicionar bloco:
  ```php
  } catch (\Illuminate\Database\QueryException $e) {
      $message = 'Já existe um recibo para este sócio nesta competência.';
      return $request->expectsJson()
          ? response()->json(['message' => $message], 422)
          : redirect()->back()->withErrors(['competencia' => $message]);
  }
  ```

---

## Checklist completo (sem achados adicionais)

**Isolamento multi-tenant:** correto. `store` chama `$this->authorize('update', $company)` — Policy verifica
`$company->user_id === $user->id`. `update` e `destroy` executam `abort_if(record->company_id !== company->id, 404)`
seguido de `$this->authorize('update'/'delete', $record)`. `dashboardRecordsForCompanies` filtra via
`whereIn('company_id', $companyIds)`, onde `$companyIds` deriva de `ListCompaniesUseCase($userId)`.

**Validação de `partner_id` (store):** `StoreProlaboreRecordRequest` usa closure com `DB::table('company_partners')
->where('company_id', $companyId)->where('id', $value)->whereNull('deleted_at')->exists()`. Correto — soft-deleted
partners não passam, e o `company_id` vem do route model binding já autorizado.

**Competência mês corrente:** `$value !== date('Y-m')` — comparação string simples, sem interpolação SQL,
sem input não-sanitizado. Correto.

**SQL injection em closures de validação:** todos os `DB::table(...)` usam `->where('coluna', $binding)` —
parâmetros via PDO bindings. Sem `whereRaw` com variáveis interpoladas.

**Mass assignment:** `ProlaboreRecordModel` usa `$fillable` explícito. `EloquentProlaboreRecordRepository::save()`
constrói `$data` a partir do objeto de domínio, sem `$request->all()`.

**`origem` no present():** expõe `'automatico'` ou `'manual'` — informação de auditoria adequada, sem PII.

**RN8 (`origem = 'manual'` no update):** `ProlaboreRecord::update()` hardcoda `origem: 'manual'`. Correto e
localizado no domínio, não no controller.

**`partner_id` e `record_id` no dashboard:** valores vêm exclusivamente do banco (chaves dos arrays de records
e configs), nunca de input do usuário. Sem risco de IDOR — a query de records já filtra por `company_id`
pertencente ao usuário.

**Configuração e debug:** sem `dd()`, `dump()`, `var_dump()`, `console.log()` ou credenciais hardcoded.

**Performance:** sem N+1 introduzido. `dashboardRecordsForCompanies` já busca todos os records em lote; a
adição de `id` ao `->get([...])` não altera o plano de execução. As duas queries adicionais nas
FormRequests (partner_id exists, duplicata exists) são pontuais por requisição — aceitável.
