# Auditor — Padrões de Verificação

> Referência consultada sob demanda pelo `auditor/SKILL.md`. Checklist por categoria + dicas para evitar
> falsos positivos.

## Como usar
1. Para cada item, **primeiro confirme se o problema realmente existe no código auditado** — leia o arquivo,
   não infira pelo nome. Falso positivo é pior que omissão.
2. Se o item depende de algo fora do escopo da feature (ex: middleware global), olhe o `bootstrap/app.php`
   ou `routes/` antes de reportar.
3. Severidade é sobre **impacto real**, não sobre o nome do problema. Mass assignment numa tabela admin é
   crítico; num campo `notes` opcional pode ser baixo.

## Severidade

| Nível | Quando usar |
|-------|-------------|
| **CRÍTICO** | Vazamento de dados entre usuários, RCE, bypass de autenticação, secret commitado. Bloqueia merge. |
| **ALTO** | Vulnerabilidade explorável com esforço (IDOR sem ownership check, mass assignment em campos sensíveis, SQL injection). Resolver antes de produção. |
| **MÉDIO** | Hardening importante (rate limit ausente, falta de `$hidden` em campo PII, N+1 que vai morder em escala). Próxima sprint. |
| **BAIXO** | Defesa em profundidade (header CSP ausente quando não há renderização perigosa, índice ausente em coluna pouco usada). |
| **INFO** | Apenas se for **educativo e acionável** — não use para "lembrete genérico". |

## Segurança — Checklist (primário)

### Isolamento Multi-tenant
- Toda query de negócio filtra por `user_id` (ou `company_id` quando aplicável).
- Policy verifica **ownership**, não só autenticação (`$this->authorize` ou `Gate::authorize` no controller).
- Endpoints com ID na rota verificam que o recurso pertence ao usuário (não confiar só no FK).
- Soft-delete não vaza registros entre usuários (scope global aplicado).
- **Não reporte como problema** se há `auth` + Policy registrada + `authorize()` no controller — verifique o trio.

### Autenticação e Autorização
- Rotas protegidas por `auth` middleware; rotas de escrita por `verified` quando pertinente.
- Policies registradas no `AuthServiceProvider` (ou auto-discovery) e **aplicadas** no controller.
- Escalação de privilégio: parâmetros sensíveis (role, owner_id) não chegam pelo `$request->all()`.

### Injeção e Validação (OWASP A03)
- `whereRaw`, `orderByRaw`, `selectRaw` — parâmetros via bindings, **nunca** interpolados.
- Mass Assignment: `$fillable` presente; **nunca** `$guarded = []`.
- Uploads: tipo MIME validado server-side (não só extensão), nome sanitizado, fora de `public/`.
- Todos os inputs chegam ao Service já validados (`FormRequest` ou `Validator::make`) — **não reporte se já
  há FormRequest cobrindo o campo**.

### Dados Sensíveis (OWASP A02)
- PII (CPF, CNPJ, dados bancários) **não** aparece em logs (`Log::info`, `dd`, `dump`, `report()`).
- Campos sensíveis com `$hidden` no model (password, remember_token, tokens de acesso).
- Secrets via `.env` + `config()` — **nunca** hardcoded no código commitado.
- Responses não expõem campos internos desnecessários (sem `toArray()` irrestrito de model com PII).

### CSRF e Middleware
- Rotas de escrita dentro do middleware `web` (CSRF automático via Inertia). Rotas fora do `web` precisam
  de proteção equivalente (token bearer + validação).
- Rate limiting em autenticação e operações custosas (`throttle` middleware).

### Configuração e Debug
- Sem `dump`, `dd`, `var_dump`, `print_r`, `console.log` (no JS gerado) no código.
- Sem credenciais, chaves de API ou URLs internas hardcoded.

## Performance — Checklist (secundário)

- **N+1:** relações carregadas com `with()` onde a coleção cresce. Suspeite de iterações sobre `$collection`
  acessando relação. **Confirme com tinker ou query log antes de reportar** — Eloquent pode estar usando
  `lazy()` ou eager implícito.
- **Índices:** FKs (`user_id`, `company_id`, colunas de filtro frequente) com índice no migration.
- **Select \*:** queries em listagens com muitas colunas — `->select([...])` quando o response usa só algumas.
- **Paginação:** listagens sem `->get()` irrestrito — `->paginate()` ou `->cursorPaginate()`. Não reporte
  para listagens com cap natural (ex: 10 sócios).
- **Payload:** response não embute relações aninhadas desnecessárias para a tela atual.

## Anti-ruído — o que NÃO reportar

- "Falta `try/catch`" genérico — Laravel já tem handler global. Só reporte se o erro vazar info sensível.
- "Falta validação" quando há `FormRequest` cobrindo. Leia o request class antes.
- "Sem teste" — é trabalho do `/tester`, não do auditor.
- "Nome de variável ruim", "código não documentado", style — não é auditoria.
- Sugestões de refatoração que não mudam postura de segurança ou performance.
- Itens INFO que são "boa prática genérica" sem ação concreta para esta feature.

> **Regra:** se o achado não muda comportamento do sistema ou postura de segurança/performance,
> ele não vai no relatório.
