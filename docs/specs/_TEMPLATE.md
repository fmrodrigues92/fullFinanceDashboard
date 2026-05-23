# Spec: {Nome da Feature}

- **Bounded Context:** {ex.: Invoicing, Clients, Simulations}
- **Status:** rascunho | aprovada | em desenvolvimento | concluída
- **Autor:** /gerente
- **Data:** {AAAA-MM-DD}

## 1. Objetivo de negócio
{O que o usuário ganha com isto e por quê. 1–3 frases.}

## 2. Atores
{Quem usa. Lembrar: todo dado é isolado por `user_id`.}

## 3. User stories
- Como {ator}, quero {ação} para {benefício}.

## 4. Regras de negócio
- RN1: {regra}
- RN2: {regra}

## 5. Critérios de aceite
- [ ] CA1: {comportamento verificável}
- [ ] CA2: {comportamento verificável}

## 6. Escopo
**Dentro:** {...}
**Fora (nesta entrega):** {...}

## 7. Modelo de dados proposto
{Entidades, campos, relacionamentos. Toda tabela de negócio tem `user_id` (FK → users).}

## 8. Contrato preliminar (rascunho)
{Endpoints prováveis — o /backend formaliza isto em docs/contracts/ depois.}

| Método | Rota | Descrição | Auth/Policy |
|--------|------|-----------|-------------|
| GET | /... | ... | sim, por user_id |

## 9. Dependências e riscos
- {dependências de outras features, integrações, riscos}

## 10. Handoff
- [ ] Spec aprovada pelo usuário
- [ ] `docs/progress/STATUS.md` atualizado
- [ ] Pronta para `/backend`
