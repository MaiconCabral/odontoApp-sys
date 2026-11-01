# Documentação do frontend

Regras e decisões do frontend do odontoApp (Nuxt 3, Vue 3, TypeScript e Tailwind CSS). Consulte antes de criar páginas, telas de autenticação ou componentes visuais.

As regras gerais de código estão em [`.cursor/rules/frontend.md`](../../.cursor/rules/frontend.md). Estes documentos cobrem o que é específico da aplicação.

| Documento | Conteúdo |
| --- | --- |
| [Arquitetura](arquitetura.md) | Rotas por clínica, renderização, stores e services |
| [Autenticação](autenticacao.md) | Fluxos, regras de formulário, segurança e troca do mock pela API |
| [Identidade visual](identidade-visual.md) | Cores, tema, tamanhos e direção das telas de entrada |
| [Testes](testes.md) | Sessão de teste e o que cobrir |

## Estado atual

> **A autenticação é simulada e não pode ir para produção com dados reais.** Nenhuma rota tem proteção real enquanto a API não existir.

Todos os dados do painel também vêm de `mocks/`.

Para testar localmente, rode `npm run dev` e entre em `/login` com `demo@sorriso.app` / `Sorriso123`. Essa conta só existe no mock.

## Ainda não implementado

- API real de autenticação e de clínicas.
- Status rápido de consultas no dashboard.
- Paginação real, histórico e prontuário de pacientes.
- Faturas e upgrade de plano com Stripe.
- Agenda com FullCalendar e gráficos com ApexCharts.
- Verificação de e-mail e aceite de termos/LGPD.

## Problemas visuais conhecidos

- O branco sobre `#0284c7` nos botões tem contraste de cerca de 4,1:1, abaixo dos 4,5:1 do WCAG AA.
- Os cartões de status em tons pastel continuam claros no tema escuro.
