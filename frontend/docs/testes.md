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
- responde às chamadas de `$fetch` com os dados de `tests/fixtures/`, para que os services reais rodem. Uma chamada sem resposta definida falha o teste.

Para simular outra resposta, use `vi.spyOn` no service dentro do próprio arquivo de teste. Um spy no arquivo de setup não alcança a instância usada pelas páginas.

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

## Confira também no navegador

Os testes de componente não exercitam o roteamento real nem o resultado visual. Ao mexer em autenticação, rotas ou tema, confira no build de produção:

- o redirecionamento sem sessão e a volta à página pedida;
- a troca de contas na mesma aba, sem dados da conta anterior na tela, incluindo avisos e modais;
- os temas claro e escuro, em 375px e em desktop.
