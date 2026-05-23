# Contrato: {Nome da Feature}

- **Bounded Context:** {...}
- **Spec de origem:** `docs/specs/{feature}.md`
- **Status:** publicado | revisado
- **Autor:** /backend
- **Data:** {AAAA-MM-DD}

> Esta é a **fonte da verdade** entre backend e frontend. O frontend só confia no que está aqui.
> Toda operação é autenticada e isolada por `user_id` via Policy.

## Endpoints

### {Verbo} {/rota}
- **Descrição:** {...}
- **Auth:** obrigatória · **Policy:** {nome da policy / regra de propriedade}
- **Wayfinder:** `{nome.da.rota}`

**Request**
```json
{
  "campo": "tipo + validação (ex.: string, max:255, required)"
}
```

**Response 200**
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
