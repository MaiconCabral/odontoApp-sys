# Integração com a API

Como o frontend conversa com o backend. Os endpoints estão em [backend/docs/api.md](../../backend/docs/api.md).

## Clínica pelo subdomínio

`useClinicHost` lê o host do navegador:

| Host | `isClinicHost` | Uso |
| --- | --- | --- |
| `localhost:3000` | `false` | Encontrar a clínica e cadastrar uma nova |
| `sorriso-perfeito.localhost:3000` | `true` | Login, recuperação de senha e painel da clínica |

- `centralUrl(path)` e `clinicUrl(slug, path)` montam links entre os hosts, mantendo protocolo e porta.
- O domínio central vem de `NUXT_PUBLIC_CENTRAL_DOMAIN`.
- Na mesma clínica, use `NuxtLink`. Para trocar de host, use `<a :href>` ou `navigateTo(url, { external: true })`, porque a sessão não acompanha a troca de host.

## `useApi`

Toda chamada passa por `useApi().api(path, options)`, que:

1. chama `NUXT_PUBLIC_API_BASE`, trocando `{host}` pelo host do navegador, de modo que cada clínica chama o próprio subdomínio da API;
2. envia os cookies e o cabeçalho `Accept: application/json`;
3. antes da primeira requisição que altera dados, busca `/sanctum/csrf-cookie` e envia o token em `X-XSRF-TOKEN`;
4. diante de um `419`, renova o cookie e tenta mais uma vez;
5. troca os nomes dos campos de erro para camelCase (`clinic_name` → `clinicName`), para o erro chegar ao campo certo do formulário.

## Services

| Service | API | Observação |
| --- | --- | --- |
| `authService` | sessão, login, cadastro, senha, logout | `getSession` devolve `null` em 401 ou 403 |
| `agendaService` | `GET /api/agenda` | |
| `clinicService` | `GET` e `PUT /api/clinic` | Troca `null` por texto vazio |
| `usersService` | `/api/users` | Converte o formulário para snake_case |
| `settingsService` | `GET /api/clinic` e mock | Preferências, horários e assinatura ainda são mock |
| `overviewService`, `patientsService` | mock | Ainda sem API |

## Papéis no frontend

| Papel | Menu | Página inicial |
| --- | --- | --- |
| `admin` | Visão geral, Agenda, Pacientes, Configurações | `/app` |
| `reception` | Visão geral, Agenda, Pacientes | `/app` |
| `professional` | Agenda, sem o botão "Novo agendamento" | `/app/agenda` |

- O menu vem de `mocks/workspace.ts`, filtrado por `roles` em `useWorkspace`.
- As páginas restritas usam `definePageMeta({ middleware: 'role', roles: [...] })`.
- `homePath(role)` define a página inicial do papel.
- Tudo isso só organiza a tela. Quem bloqueia o acesso é a API.

## Telas desta etapa

- **Login (`pages/login.vue`):** no domínio central, mostra `AuthClinicFinder`, que pede o endereço da clínica. Para um subdomínio inexistente, mostra "Clínica não encontrada". Depois do cadastro, mostra a confirmação com o e-mail já preenchido.
- **Cadastro (`pages/cadastro.vue`):** só no domínio central. Ao terminar, abre o login da nova clínica.
- **Configurações → Clínica (`SettingsClinicForm`):** salva na API e atualiza o nome da clínica na barra lateral.
- **Configurações → Equipe (`SettingsTeamCard` e `SettingsUserModal`):**
  - lista a equipe e permite criar, editar e excluir;
  - a exclusão pede um segundo clique;
  - o próprio usuário não aparece com o botão de excluir e não pode trocar o próprio papel;
  - os erros da API aparecem no campo ou no topo do modal.

## Ambiente local

```bash
# frontend/.env
NUXT_PUBLIC_API_BASE=http://{host}:8000
NUXT_PUBLIC_CENTRAL_DOMAIN=localhost
```

Abra por `localhost` ou `<slug>.localhost`, nunca por `127.0.0.1`.

Se a porta 3000 estiver ocupada, o Nuxt sobe em outra porta, e a API aceita qualquer porta de `*.localhost`. Os links dos e-mails, porém, usam `FRONTEND_URL_TEMPLATE`, no `.env` do backend, que aponta para a 3000.
