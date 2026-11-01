# Arquitetura

## Pastas

| Pasta | Responsabilidade |
| --- | --- |
| `pages/` | Rotas da aplicação |
| `layouts/` | `default` para o painel, `auth` para as telas de entrada |
| `middleware/` | Controle de navegação: `auth`, `guest` e `tenant` |
| `components/` | Componentes por módulo (`agenda/`, `auth/`, `patients/`...) e compartilhados (`app/`) |
| `composables/` | Regras de negócio reutilizáveis |
| `stores/` | Estado Pinia compartilhado entre rotas |
| `services/` | Acesso aos dados; é a única camada que conhece mocks ou API |
| `mocks/` | Dados simulados, enquanto a API não existe |
| `types/` | Tipos de dados e contratos |
| `utils/` | Funções puras, como validações e formatação |

O caminho dos dados é: página ou componente → composable → service → mock ou API.

## Rotas

| Rota | Acesso | Conteúdo |
| --- | --- | --- |
| `/` | Público | Redireciona para `/login` |
| `/login` | Só sem sessão | Entrada |
| `/cadastro` | Só sem sessão | Cadastro da clínica e do administrador |
| `/recuperar-senha` | Só sem sessão | Pedido do link de redefinição |
| `/redefinir-senha?token&email` | Só sem sessão | Nova senha |
| `/app/[tenant]` | Com sessão | Visão geral |
| `/app/[tenant]/agenda` | Com sessão | Agenda |
| `/app/[tenant]/pacientes` | Com sessão | Pacientes |
| `/app/[tenant]/configuracoes` | Com sessão | Configurações |

### Regras das rotas do painel

- A clínica é identificada pelo slug no caminho da URL, não por subdomínio.
- Toda página nova do painel fica em `pages/app/[tenant]/`.
- A proteção fica só na rota pai, `pages/app/[tenant].vue`, e vale para todas as páginas filhas. Não repita os middlewares `auth` e `tenant` nelas.
- Nunca escreva links fixos para o painel, como `to="/agenda"`. Use `useTenantPath`:

```ts
const tenantPath = useTenantPath()

tenantPath()         // /app/sorriso-perfeito
tenantPath('agenda') // /app/sorriso-perfeito/agenda
```

- Para adicionar um item ao menu, inclua-o em `mocks/workspace.ts` com o `segment` da rota. `useWorkspace` monta o caminho completo.

### Middlewares

| Middleware | Comportamento |
| --- | --- |
| `auth` | Carrega a sessão uma vez. Sem sessão, vai para `/login?redirect=<destino>` |
| `guest` | Com sessão, vai para o painel da clínica |
| `tenant` | Se o slug da URL não for o da sessão, vai para o painel da clínica do usuário |

Eles só organizam a navegação. **Quem protege os dados é a API**, que deve validar a sessão e a clínica em cada requisição.

## Renderização

Definida em `routeRules`, no `nuxt.config.ts`:

- O painel (`/app/**`) e as telas de autenticação rodam só no navegador (`ssr: false`). Toda rota nova de autenticação precisa entrar nessa lista.
- O SSR continua ligado por padrão, para futuras páginas públicas com SEO.

## Estado

Use uma store apenas quando o estado precisa ser compartilhado entre rotas. Nos demais casos, mantenha `ref` e `computed` locais.

| Store | Conteúdo |
| --- | --- |
| `useAuthStore` | Usuário logado e indicador de sessão já carregada |
| `useTenantStore` | Clínica atual, slug e plano |
| `useAgendaFiltersStore` | Profissionais e salas selecionados; persistem ao trocar de rota |

A data e a visão da agenda (dia, semana, mês) continuam locais em `useAgendaCalendar`.

## Services

- Composables não importam `mocks/`; eles chamam um service.
- Para ligar um módulo à API, altere só o service, usando `useApi` para enviar os cookies.
- O diretório `services/` tem auto-import, configurado em `imports.dirs`.

```ts
export function useOverview() {
  return useAsyncData('overview', () => overviewService.get())
}
```

## Limpeza ao sair da conta

**Todo estado com dados da clínica precisa ser limpo em `logout`, em `composables/useAuth.ts`.** Sem isso, a próxima conta na mesma aba vê dados da clínica anterior.

Ao criar uma store ou um `useState` com dados da clínica ou de pacientes, inclua a limpeza nessa função. Hoje ela limpa:

- sessão, clínica e filtros da agenda;
- pacientes e agendamentos criados na sessão;
- toast e modal abertos, que podem conter nome de paciente;
- cache do `useAsyncData`, com `clearNuxtData`.
