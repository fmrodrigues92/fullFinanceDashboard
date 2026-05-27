# Spec: {Nome da Feature}

- **Bounded Context:** {ex.: Invoicing, Clients, Simulations}
- **Status:** rascunho | aprovada | em desenvolvimento | concluída
- **Autor:** /gerente
- **Data:** {AAAA-MM-DD}

> Esta spec descreve **o quê e por quê** (negócio). A **fronteira técnica** (endpoints, schemas, tipos)
> vive em `docs/contracts/{feature}.md`. Seja direta: sem volume gratuito, sem repetir o template.

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

## 8. Dependências e riscos
- {dependências de outras features, integrações, riscos}

## 9. Handoff
- [ ] Spec aprovada pelo operador
- [ ] Contrato publicado em `docs/contracts/{feature}.md`
- [ ] `docs/progress/STATUS.md` atualizado
- [ ] Pronta para `/backend` e `/frontend` (paralelo)
