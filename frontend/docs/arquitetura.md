# Arquitetura

## Pastas

| Pasta | Responsabilidade |
| --- | --- |
| `pages/` | Rotas da aplicação |
| `layouts/` | `default` para o painel, `auth` para as telas de entrada |
| `middleware/` | Controle de navegação: `auth`, `guest`, `role`, `clinic-host` e `central-host` |
| `components/` | Componentes por módulo (`agenda/`, `auth/`, `patients/`...) e compartilhados (`app/`) |
| `composables/` | Regras de negócio reutilizáveis |
| `stores/` | Estado Pinia compartilhado entre rotas |
| `services/` | Acesso aos dados; é a única camada que conhece mocks ou API |
| `mocks/` | Dados dos módulos que ainda não têm API |
| `types/` | Tipos de dados e contratos |
| `utils/` | Funções puras, como validações e formatação |

O caminho dos dados é: página ou componente → composable → service → mock ou API.

## Rotas

| Rota | Acesso | Conteúdo |
| --- | --- | --- |
| `/` | Público | Redireciona para `/login` |
| `/login` | Só sem sessão | No domínio central, pede o endereço da clínica. No subdomínio, é a entrada |
| `/cadastro` | Domínio central | Cadastro da clínica e do administrador |
| `/recuperar-senha` | Subdomínio, sem sessão | Pedido do link de redefinição |
| `/redefinir-senha?token&email` | Subdomínio, sem sessão | Nova senha |
| `/agendar` | Subdomínio, público | Pedido de agendamento do paciente. Aceita `date`, `time`, `professional_id` e `procedure_id` na query (link da lista de espera) |
| `/consulta?token` | Subdomínio, público | Paciente confirma ou cancela pelo link do e-mail |
| `/admin/login` | Domínio central | Entrada do painel da plataforma |
| `/admin`, `/admin/clinicas/[id]`, `/admin/planos` | Domínio central, admin da plataforma (`platform-auth`) | Clínicas e planos. Layout `platform` |
| `/app` | Admin e recepção | Visão geral, com dados da API |
| `/app/agenda` | Todos os papéis | Agenda, pedidos pendentes e bloqueios |
| `/app/pacientes` | Admin e recepção | Cadastro de pacientes |
| `/app/pacientes/[id]` | Todos os papéis; a API decide quem vê cada paciente | Ficha com consultas, prontuário, odontograma e anexos |
| `/app/lista-espera` | Admin e recepção | Lista de espera |
| `/app/relatorios` | Admin | Relatórios por período, com exportação CSV |
| `/app/configuracoes` | Admin | Clínica, equipe, horários, procedimentos, salas e demais configurações |

### Regras das rotas do painel

- A clínica é identificada pelo subdomínio, como `sorriso-perfeito.localhost`. `useClinicHost` lê o host e monta links entre o domínio central e as clínicas.
- Toda página nova do painel fica em `pages/app/`.
- A sessão fica na rota pai, `pages/app.vue`, e vale para todas as páginas filhas. Não repita o middleware `auth` nelas.
- Páginas restritas declaram os papéis: `definePageMeta({ middleware: 'role', roles: ['admin'] })`.
- Monte links do painel com `panelPath`:

```ts
panelPath()         // /app
panelPath('agenda') // /app/agenda
```

- Para adicionar um item ao menu, inclua-o em `mocks/workspace.ts` com o `segment` da rota e os `roles` que o veem. `useWorkspace` filtra pelo papel e monta o caminho.

### Middlewares

| Middleware | Comportamento |
| --- | --- |
| `auth` | Carrega a sessão uma vez. Sem sessão, vai para `/login?redirect=<destino>` |
| `guest` | Com sessão, vai para a página inicial do papel: agenda para profissionais, visão geral para os demais |
| `role` | Se o papel não estiver em `meta.roles`, vai para a página inicial do papel |
| `clinic-host` | No domínio central, vai para `/login` |
| `central-host` | Em um subdomínio, leva a mesma rota para o domínio central |

Eles só organizam a navegação. **Quem protege os dados é a API**, que valida a sessão, a clínica e o papel em cada requisição. Veja [backend/docs/multitenancy.md](../../backend/docs/multitenancy.md).

## Renderização

Definida em `routeRules`, no `nuxt.config.ts`:

- O painel (`/app/**`), as telas de autenticação e a página `/consulta` rodam só no navegador (`ssr: false`). Toda rota nova de autenticação ou com token na URL precisa entrar nessa lista.
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
- Para ligar um módulo à API, altere só o service, usando `useApi`.
- `useApi` chama a API no mesmo host do navegador, envia os cookies, cuida do CSRF e traduz os nomes de campo dos erros para camelCase. `NUXT_PUBLIC_API_BASE` usa `{host}` no lugar do host.
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
- período da agenda, busca de pacientes, contador de pedidos e avisos do sino (zerados, não removidos, porque a página ainda na tela pode estar lendo esses estados);
- equipe e demais dados do `useAsyncData`;
- pacientes e agendamentos criados na sessão;
- toast e modal abertos, que podem conter nome de paciente;
- cache do `useAsyncData`, com `clearNuxtData`.
