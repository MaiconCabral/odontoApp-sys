# Documentação do frontend

Regras e decisões do frontend do odontoApp (Nuxt 3, Vue 3, TypeScript e Tailwind CSS). Consulte antes de criar páginas, telas de autenticação ou componentes visuais.

As regras gerais de código estão em [`.cursor/rules/frontend.md`](../../.cursor/rules/frontend.md). Estes documentos cobrem o que é específico da aplicação.

| Documento | Conteúdo |
| --- | --- |
| [Arquitetura](arquitetura.md) | Subdomínios, rotas, papéis, renderização, stores e services |
| [Autenticação](autenticacao.md) | Fluxos, regras de formulário e segurança |
| [Integração com a API](integracao-api.md) | `useApi`, subdomínios, services, papéis, agenda, `/agendar`, `/consulta`, visão geral e configurações |
| [Identidade visual](identidade-visual.md) | Cores, tema, tamanhos e direção das telas de entrada |
| [Testes](testes.md) | Sessão de teste e o que cobrir |

## Estado atual

Usam a API do `backend/`:

- autenticação, clínica e equipe;
- agenda, pacientes, expediente e o pedido online;
- procedimentos e salas;
- avisos ao paciente e a página `/consulta`;
- visão geral.

Preferências e assinatura ainda vêm de `mocks/`.

Para testar localmente:

1. Em `backend/`, rode `php artisan migrate:fresh --seed` e `php artisan serve`.
2. Em `frontend/`, rode `npm run dev`.
3. Abra `http://sorriso-perfeito.localhost:3000/login`. As contas estão em [backend/docs/multitenancy.md](../../backend/docs/multitenancy.md), e a senha é a de `SEED_DEMO_PASSWORD`, no `.env` do backend.

A documentação do backend começa em [backend/docs/README.md](../../backend/docs/README.md).

## Ainda não implementado

- Avisos por WhatsApp.
- Gestão global de clínicas e de planos.
- Status rápido de consultas no dashboard.
- Histórico clínico e prontuário de pacientes.
- Faturas e upgrade de plano com Stripe.
- Agenda com FullCalendar e gráficos com ApexCharts.
- Verificação de e-mail e aceite de termos/LGPD.

## Problemas visuais conhecidos

- O branco sobre `#0284c7` nos botões tem contraste de cerca de 4,1:1, abaixo dos 4,5:1 do WCAG AA.
- Os cartões de status em tons pastel continuam claros no tema escuro.
