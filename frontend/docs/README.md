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
- visão geral;
- preferências de atendimento e assinatura, em Configurações;
- sino de avisos para a equipe, no topo;
- ficha do paciente com consultas, prontuário, odontograma e anexos;
- lista de espera, com o link do e-mail que preenche o `/agendar`.

`mocks/settings.ts` guarda só textos fixos: abas, títulos das preferências e itens extras de cada plano. Os valores vêm da API. Detalhes em [integracao-api.md](integracao-api.md#configurações--preferências-e-assinatura).

Para testar localmente:

1. Em `backend/`, rode `php artisan migrate:fresh --seed` e `php artisan serve`.
2. Em `frontend/`, rode `npm run dev`.
3. Abra `http://sorriso-perfeito.localhost:3000/login`. As contas estão em [backend/docs/multitenancy.md](../../backend/docs/multitenancy.md), e a senha é a de `SEED_DEMO_PASSWORD`, no `.env` do backend.

A documentação do backend começa em [backend/docs/README.md](../../backend/docs/README.md).

## Ainda não implementado

- Avisos por WhatsApp.
- Gestão global de clínicas e de planos.
- Status rápido de consultas no dashboard.
- Faturas e upgrade de plano com Stripe. Hoje "Gerenciar assinatura" só mostra os planos e o contato.
- Regra de "Confirmação automática". A preferência é salva, mas nada ainda a usa.
- Agenda com FullCalendar e gráficos com ApexCharts.
- Verificação de e-mail e aceite de termos/LGPD.

## Problemas visuais conhecidos

Nenhum registrado. O contraste dos botões (agora 5,9:1) e as cores de status no tema escuro foram corrigidos; veja [identidade-visual.md](identidade-visual.md).
