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
| `agendaService` | `GET /api/agenda` | Recebe o período (`from`, `to`) |
| `appointmentsService` | `/api/appointments`, `/api/availability`, `/api/appointment-requests` | Criar, remarcar, status, aceite e horários livres |
| `patientsService` | `/api/patients` | Busca e paginação no servidor |
| `scheduleService` | `/api/business-hours`, `/api/schedule-blocks` | Expediente e bloqueios |
| `publicBookingService` | `/api/public/booking`, `availability`, `booking-requests` | Página `/agendar`, sem sessão |
| `patientLinksService` | `/api/public/appointments/{token}` | Página `/consulta`, sem sessão |
| `clinicService` | `GET` e `PUT /api/clinic` | Troca `null` por texto vazio |
| `usersService` | `/api/users` | Converte o formulário para snake_case |
| `proceduresService` | `/api/procedures` | Converte o preço digitado (`"450,00"`) para `price_cents` |
| `roomsService` | `/api/rooms` | |
| `settingsService` | `GET /api/clinic` e mock | Preferências e assinatura ainda são mock |
| `overviewService` | `GET /api/overview` | Admin e recepção |

Os valores em dinheiro chegam em centavos. Use `formatMoney`, `toCents` e `centsToInput`, de `utils/money.ts`, para exibir e editar.

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

## Agenda

- `useAgenda()` carrega o período de `useAgendaRange()`. Enquanto o período for `null`, vale o período padrão da API (a semana atual e as cinco seguintes).
- `useAgendaCalendar` pede um período novo só quando a semana, o dia ou o mês na tela saem do período carregado.
- A grade usa linhas de 30 minutos; cada consulta ocupa a altura da duração, com a cor do status (`STATUS_CLASSES`, em `utils/appointments.ts`). Consultas no mesmo horário ficam lado a lado.
- Bloqueios aparecem hachurados. Admin e recepção clicam neles para excluir.
- Os modais ficam no layout `default` e abrem com `useActiveModal().openModal(nome, payload)`:

  | Modal | Payload | Uso |
  | --- | --- | --- |
  | `appointment` | `{ appointment? }` | Novo agendamento ou remarcação |
  | `appointment-details` | `{ id }` | Dados, avisos ao paciente, histórico e ações permitidas |
  | `block` | `{ block? }` | Novo bloqueio, ou ver e excluir um existente |
  | `patient` | `{ patient? }` | Novo paciente ou edição |

- Os botões de ação vêm de `actions`, devolvido pela API. O front não decide quem pode o quê.
- Depois de qualquer mudança, `refreshAgendaData()` recarrega a agenda e os pedidos pendentes.
- O layout carrega os pedidos pendentes para admin e recepção, e o número aparece ao lado de Agenda no menu.
- O procedimento é identificado pelo `id` (`ProcedureOption.id`), não mais por uma chave fixa. O agendamento, a remarcação e o aceite de pedido só oferecem procedimentos e salas ativos. Uma consulta antiga em sala desativada continua na agenda, mas, para ser remarcada, precisa de outra sala.
- A seção "Avisos ao paciente" do modal de detalhes lista o que foi enviado e mostra "Paciente sem e-mail: avise por telefone" quando não há e-mail.

## Página pública `/agendar`

- Só no subdomínio da clínica (middleware `clinic-host`), sem sessão.
- Tem três etapas: atendimento, dia e horário, dados de contato.
- Se o horário for ocupado enquanto o paciente digita, a página volta para a escolha de horários, com a mensagem da API e horários atualizados.
- O campo oculto `website` é a armadilha para robôs. Não remova nem torne visível.
- Os procedimentos aparecem sem preço.

## Página do paciente `/consulta`

- É aberta pelo link dos e-mails (`/consulta?token=...`), no subdomínio da clínica, sem sessão e com o layout `auth`.
- Mostra a data, o procedimento, o profissional e o endereço, com os botões que a API libera (`canConfirm`, `canCancel`):
  - "Confirmo presença" confirma na hora;
  - "Preciso cancelar" pede um segundo clique ("Sim, cancelar a consulta").
- Sem token, com token expirado ou com consulta remarcada, a página mostra "Link inválido ou expirado" e orienta a falar direto com a clínica.
- `routeRules` em `nuxt.config.ts` renderiza a rota só no navegador (`ssr: false`), como as páginas de entrada.

## Visão geral

- `pages/app/index.vue` usa `useAsyncData` com `overviewService.get()`. A API entrega o texto já formatado ("R$ 800", "12,5%"), e o front só exibe.
- `trend` (`up`, `down`, `flat`) define a seta e a cor das métricas e do gráfico semanal.
- Clicar num paciente de "Próximos pacientes" abre o modal `appointment-details`.

## Telas desta etapa

- **Login (`pages/login.vue`):** no domínio central, mostra `AuthClinicFinder`, que pede o endereço da clínica. Para um subdomínio inexistente, mostra "Clínica não encontrada". Depois do cadastro, mostra a confirmação com o e-mail já preenchido.
- **Cadastro (`pages/cadastro.vue`):** só no domínio central. Ao terminar, abre o login da nova clínica.
- **Configurações → Clínica (`SettingsClinicForm`):** salva na API e atualiza o nome da clínica na barra lateral.
- **Configurações → Equipe (`SettingsTeamCard` e `SettingsUserModal`):**
  - lista a equipe e permite criar, editar e excluir;
  - a exclusão pede um segundo clique;
  - o próprio usuário não aparece com o botão de excluir e não pode trocar o próprio papel;
  - os erros da API aparecem no campo ou no topo do modal.
- **Configurações → Procedimentos e Salas (`SettingsProceduresCard`, `SettingsProcedureModal`, `SettingsRoomsCard` e `SettingsRoomModal`):**
  - só para admin;
  - seguem o padrão da equipe: lista, modal de criar e editar, ativar e desativar, e exclusão com segundo clique;
  - o procedimento tem duração de 30 a 240 minutos e preço em reais;
  - excluir um item já usado em consultas mostra a mensagem da API, que sugere desativar;
  - `useProcedures` e `useRooms`, em `composables/useCatalog.ts`, guardam as listas. Depois de salvar, a agenda é recarregada.

## Ambiente local

```bash
# frontend/.env
NUXT_PUBLIC_API_BASE=http://{host}:8000
NUXT_PUBLIC_CENTRAL_DOMAIN=localhost
```

Abra por `localhost` ou `<slug>.localhost`, nunca por `127.0.0.1`.

Se a porta 3000 estiver ocupada, o Nuxt sobe em outra porta, e a API aceita qualquer porta de `*.localhost`. Os links dos e-mails, porém, usam `FRONTEND_URL_TEMPLATE`, no `.env` do backend, que aponta para a 3000.
