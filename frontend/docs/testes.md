# Testes

As regras gerais estão em [`.cursor/rules/testing.md`](../../.cursor/rules/testing.md). O projeto usa Vitest, Vue Test Utils e `@nuxt/test-utils`.

```bash
npm test           # testes
npm run typecheck  # tipos
```

Playwright não é dependência do projeto. Os testes de navegador feitos até agora foram manuais, com a ferramenta `playwright-cli`.

## Sessão de teste

`tests/setup.ts` roda antes de cada teste e:

- entra com a sessão de administrador de `tests/fixtures/session.ts`;
- limpa os filtros da agenda, para não vazarem entre testes;
- desfaz os `vi.spyOn` do teste anterior;
- responde às chamadas GET de `$fetch` com os dados de `tests/fixtures/`, para que os services reais rodem. Uma chamada sem resposta definida falha o teste;
- zera o período da agenda e a busca de pacientes.

Para simular outra resposta ou uma gravação (POST, PUT, DELETE), use `vi.spyOn` no service dentro do próprio arquivo de teste. Um spy no arquivo de setup não alcança a instância usada pelas páginas.

Erros simulados num spy de service usam os campos em camelCase (`startsAt`, `professionalId`), porque é assim que `useApi` os entrega.

Não use `vi.useFakeTimers()` em testes que clicam depois: o Vue descarta cliques com horário anterior ao do registro do evento, e o relógio falso bagunça essa comparação. Para esperar um debounce, aguarde com `setTimeout` real.

Por isso, os testes já começam logados. Para testar um visitante, use o helper:

```ts
import { professionalSession } from '../fixtures/session'
import { clearSession, seedSession } from '../utils/session'

clearSession()
seedSession(professionalSession) // outro papel
```

## Rotas nos testes

Componentes que dependem da rota atual precisam recebê-la na montagem:

```ts
await mountSuspended(Sidebar, {
  props: { collapsed: false, mobileOpen: false },
  route: '/app',
})
```

Componentes que dependem do host usam `mockNuxtImport('useClinicHost', ...)`, porque o ambiente de teste roda sempre no mesmo domínio.

## O que cobrir

- **Telas de autenticação:** erro de validação, erro da API, API fora do ar, carregamento e envio duplicado.
- **Redirecionamento:** cada novo destino aceito precisa de teste em `tests/utils/auth.test.ts`, incluindo tentativas de sair para outro site.
- **Papéis:** todo item de menu ou ação restrita precisa de teste com os três papéis. A restrição real é testada no backend.
- **Logout:** todo estado novo com dados da clínica entra no teste "does not leave clinic data for the next account on logout".
- **Cadastros com modal** (equipe, procedimentos, salas): validação antes do envio, erro da API no campo, exclusão bloqueada pela API e o segundo clique para excluir. Veja `tests/components/catalog.test.ts`.
- **Páginas públicas com token** (`/consulta`): token válido, sem token e expirado (`404`), e as ações liberadas por `canConfirm` e `canCancel`. Veja `tests/pages/consulta.test.ts`.
- **Valores em dinheiro:** use `utils/money.ts` e teste a conversão em `tests/utils/forms.test.ts`.

- **Ações salvas ao clicar** (preferências): o valor enviado à API e a volta ao estado anterior quando a API falha. Veja `tests/pages/configuracoes.test.ts`.
- **Sino:** a bolinha só com aviso não lido, a lista vazia, marcar como lido e a abertura da consulta. Veja `tests/components/notifications.test.ts`.
- **Ficha do paciente:**
  - abas por papel;
  - `403`;
  - anotação e correção;
  - clique numa face do odontograma e condição do dente inteiro;
  - envio de anexos, com recusa antes do envio.
  - Veja `tests/pages/paciente.test.ts`. As regras de face e o rótulo acessível do dente estão em `tests/utils/odontogram.test.ts`.
- **Lista de espera e link do e-mail:** `tests/pages/lista-espera.test.ts` e o bloco "from the waiting list link" de `tests/pages/agendar.test.ts`.
- **Formulários enviados a um service:** passe uma cópia (`{ ...form }`). O componente limpa o formulário depois de salvar, e um spy guardaria a referência já limpa.
- **Classes em `utils/`:** o Tailwind lê `utils/**/*.ts` (`content` em `tailwind.config.ts`). Escreva cada classe por inteiro; classes montadas com texto, como `replace('fill-', 'bg-')`, não são geradas.

As respostas padrão de `/api/overview`, `/api/procedures` e `/api/rooms` estão em `tests/fixtures/agenda.ts` (`overviewFixture`, `proceduresFixture`, `roomsFixture`). As da ficha do paciente `patient-1` e de `/api/waitlist` estão em `tests/fixtures/clinical.ts`. As de `/api/clinic/preferences`, `/api/clinic/subscription` e `/api/notifications` estão em `tests/fixtures/settings.ts` (`preferencesFixture`, `subscriptionFixture`, `notificationsFixture`). O `beforeEach` de `tests/setup.ts` limpa os avisos do sino com `useNotifications().reset()`.

## Confira também no navegador

Os testes de componente não exercitam o roteamento real nem o resultado visual. Ao mexer em autenticação, rotas ou tema, confira no build de produção:

- o redirecionamento sem sessão e a volta à página pedida;
- a troca de contas na mesma aba, sem dados da conta anterior na tela, incluindo avisos e modais;
- os temas claro e escuro, em 375px e em desktop.
