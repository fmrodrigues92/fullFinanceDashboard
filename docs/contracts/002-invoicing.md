# Contrato: Cadastro de Faturamento (Feature 002)

- **Bounded Context:** `Invoicing`
- **Status:** publicado
- **Autor:** /backend
- **Data:** 2026-05-27

## Base URL

Todos os endpoints são aninhados sob `/companies/{company}/` e exigem `auth + verified`.

---

## Clientes

### `GET /companies/{company}/clients`

Lista clientes ativos da empresa (excluindo soft-deleted). Paginado — 25 por página.

**Query params**
| Param | Tipo | Descrição |
|-------|------|-----------|
| `page` | `integer` | Página desejada (default `1`) |

**Response 200**
```json
{
  "data": [
    {
      "id": 1,
      "company_id": 1,
      "nome": "Empresa Cliente Ltda",
      "ext_id": "CLI-001"
    }
  ],
  "meta": {
    "total": 50,
    "per_page": 25,
    "current_page": 1,
    "last_page": 2
  }
}
```

---

### `POST /companies/{company}/clients`

Cria um cliente.

**Body**
```json
{
  "nome": "string (required, max 255)",
  "ext_id": "string|null (opcional, max 100)"
}
```

**Response 201**
```json
{
  "id": 1,
  "company_id": 1,
  "nome": "Empresa Cliente Ltda",
  "ext_id": "CLI-001"
}
```

---

### `PUT /companies/{company}/clients/{client}`

Atualiza nome e ext_id. Mesmo body do POST.

**Response 200** — mesmo shape do POST 201.

---

### `DELETE /companies/{company}/clients/{client}`

Soft-deleta o cliente e nulifica `client_id` nas notas vinculadas.

**Response 200**
```json
{ "message": "Cliente excluído com sucesso." }
```

---

## Notas Fiscais

### `GET /companies/{company}/invoices`

Lista notas reais (não deletadas) + simulações. Paginado — 25 por página.

**Query params**
| Param | Tipo | Descrição |
|-------|------|-----------|
| `is_simulation` | `true` \| `false` | Filtra por simulação ou nota real |
| `competencia` | `YYYY-MM` | Filtra pelo mês de `data_emissao` |
| `tipo` | `nacional` \| `internacional` | Filtra pelo tipo da nota |
| `page` | `integer` | Página desejada (default `1`) |

**Response 200**
```json
{
  "data": [
    {
      "id": 1,
      "company_id": 1,
      "client_id": 2,
      "simulation_batch_id": null,
      "is_simulation": false,
      "tipo": "nacional",
      "anexo_cnae": 3,
      "data_emissao": "2026-05-01",
      "valor_brl": "10000.00",
      "valor_usd": null,
      "cotacao": null,
      "observacao": null
    }
  ],
  "meta": {
    "total": 120,
    "per_page": 25,
    "current_page": 1,
    "last_page": 5
  }
}
```

---

### `POST /companies/{company}/invoices`

Cria nota fiscal real.

**Body**
```json
{
  "client_id": 2,
  "tipo": "nacional | internacional",
  "anexo_cnae": 3,
  "data_emissao": "YYYY-MM-DD",
  "valor_brl": "10000.00",
  "valor_usd": "2000.00 | null",
  "cotacao": "5.0000 | null",
  "observacao": "string | null"
}
```

> `valor_usd` e `cotacao` são **obrigatórios** quando `tipo = internacional`; devem ser `null` para `nacional`.

**Response 201** — mesmo shape do GET item.

**Errors**
- `422` — validação falhou (client_id ausente, anexo_cnae inválido, campos USD ausentes para internacional)
- `403` — empresa de outro usuário

---

### `PUT /companies/{company}/invoices/{invoice}`

Atualiza nota real. Mesmo body do POST.

**Response 200** — mesmo shape do GET item.

---

### `DELETE /companies/{company}/invoices/{invoice}`

Soft-deleta a nota. Nota não aparece em listagens após exclusão.

**Response 200**
```json
{ "message": "Nota fiscal excluída com sucesso." }
```

---

## Lotes de Simulação

### `GET /companies/{company}/simulation-batches`

Lista lotes da empresa.

**Response 200**
```json
[
  { "id": 1, "company_id": 1 }
]
```

---

### `POST /companies/{company}/simulation-batches`

Cria lote e gera uma simulação por mês no intervalo `[data_inicio, data_termino]`.
`data_emissao` de cada simulação = primeiro dia do mês.

**Body**
```json
{
  "tipo": "nacional | internacional",
  "anexo_cnae": 3,
  "data_inicio": "YYYY-MM",
  "data_termino": "YYYY-MM",
  "valor_brl": "15000.00"
}
```

**Response 201**
```json
{ "id": 1, "company_id": 1 }
```

**Errors**
- `422` — algum mês do intervalo já tem simulação do mesmo tipo para a empresa
```json
{
  "message": "Conflito de simulações para os meses informados.",
  "conflicting_months": ["2026-02-01", "2026-03-01"]
}
```

---

### `DELETE /companies/{company}/simulation-batches/{simulationBatch}`

Remove o lote e **hard-deleta** todas as suas simulações em cascata (ON DELETE CASCADE).

**Response 200**
```json
{ "message": "Lote de simulações excluído com sucesso." }
```

---

## Regras de negócio (resumo para o frontend)

| Regra | Detalhe |
|-------|---------|
| `anexo_cnae` | Somente `3` ou `5` |
| `tipo = internacional` | `valor_usd` + `cotacao` obrigatórios |
| `tipo = nacional` | `valor_usd` e `cotacao` devem ser `null` |
| Simulação | Sem `client_id`; criada apenas via lote |
| Conflito de simulação | Máx. 1 por `(empresa, tipo, mês)` |
| Exclusão de cliente | Nulifica `client_id` nas notas, não exclui as notas |
| Exclusão de nota real | Soft delete — permanece no banco |
| Exclusão de lote | Hard delete das simulações via cascade |

---

## Internals para `/tester`

Classes com lógica de domínio para cobertura unitária:

- `Src\Invoicing\Domain\Invoice::createReal` — RN3, RN4 (cliente obrigatório; campos USD por tipo)
- `Src\Invoicing\Domain\ValueObjects\AnexoCnae` — RN5 (só 3 ou 5)
- `Src\Invoicing\Application\UseCases\CreateSimulationBatchUseCase` — RN10 (geração de meses), RN11 (detecção de conflito)
- `Src\Invoicing\Application\UseCases\DeleteClientUseCase` — RN7 (nulifica client_id após soft delete)
