# Contrato: {Nome da Feature}

- **Bounded Context:** {...}
- **Spec de origem:** `docs/specs/{feature}.md`
- **Status:** publicado | revisado
- **Autor:** /gerente (tech-lead)
- **Data:** {AAAA-MM-DD}
- **Revisões:**
  - {AAAA-MM-DD} — publicação inicial.

> Esta é a **fonte da verdade** entre backend e frontend. Ambos consomem este arquivo em paralelo.
> O `/gerente` é o dono; `/backend` e `/frontend` reportam divergências a ele para re-publicação.
> Toda operação é autenticada e isolada por `user_id` via Policy.
> **Transporte:** o app usa **Inertia.js** — leituras chegam como **props de página** (`Inertia::render`) e
> escritas respondem com **redirect + flash**. O mesmo controller devolve **JSON** quando o cliente manda
> `Accept: application/json`. Os schemas abaixo descrevem o **shape dos dados** (idêntico em props e JSON).

## Endpoints

### {Verbo} {/rota}
- **Descrição:** {...}
- **Auth:** obrigatória · **Policy:** {nome da policy / regra de propriedade}
- **Wayfinder:** `{nome.da.rota}`
- **Transporte:** Inertia (`{página, ex.: Transactions/Index}` + props) · JSON (mesmo shape sob `Accept: application/json`)

**Request**
```json
{
  "campo": "tipo + validação (ex.: string, max:255, required)"
}
```

**Response 200** _(props da página Inertia / corpo JSON — mesmo shape)_
```json
{
  "id": "number",
  "campo": "string"
}
```

**Erros**
| Status | Quando | Corpo |
|--------|--------|-------|
| 422 | validação falhou | `{ "errors": { "campo": ["..."] } }` |
| 403 | recurso de outro usuário | `{ "message": "..." }` |
| 404 | não encontrado | `{ "message": "..." }` |

## Tipos TypeScript (para o frontend)
```typescript
export interface {Recurso} {
  id: number;
  // ... espelhar a Response 1:1
}
```

## Notas de implementação relevantes ao front
- Paginação: {sim/não, formato}
- Estados vazios / loading esperados
- Campos somente-leitura vs editáveis
