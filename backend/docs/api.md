# API

Todas as respostas são JSON. Envie `Accept: application/json`.

## Convenções

- **Host:** as rotas de clínica respondem em `http://<slug>.localhost:8000`; o cadastro, em `http://localhost:8000`.
- **Sessão:** o SPA chama `GET /sanctum/csrf-cookie` antes de qualquer requisição que altera dados e manda o cookie `XSRF-TOKEN` no cabeçalho `X-XSRF-TOKEN`. Requisições sem `Origin` ou `Referer` de um domínio do SPA não recebem sessão.
- **Campos:** a requisição usa snake_case (`clinic_name`) e a resposta usa camelCase (`roleLabel`).
- **Ids:** vão como string.

## Erros

| Status | Quando | Corpo |
| --- | --- | --- |
| 401 | Sem sessão, ou usuário desativado | `{ "message": "Sessão expirada. Entre novamente." }` |
| 403 | Papel sem permissão, ou sessão de outra clínica | `{ "message": "Você não tem permissão para esta ação." }` |
| 404 | Clínica ou registro inexistente | `{ "message": "Clínica não encontrada." }` ou `"Registro não encontrado."` |
| 419 | Token CSRF ausente ou expirado | `{ "message": "Sua sessão expirou. Recarregue a página e tente novamente." }` |
| 422 | Validação | `{ "message": "...", "errors": { "campo": ["mensagem"] } }` |
| 429 | Muitas tentativas | `{ "message": "Muitas tentativas. Aguarde um minuto e tente novamente." }` |

## Domínio central

### `POST /api/clinics`

Cadastra a clínica, o banco e o administrador. Limite de 5 por minuto por IP.

```json
{
  "name": "Marina Lopes",
  "email": "marina@clinica.app",
  "password": "Sorriso123",
  "password_confirmation": "Sorriso123",
  "clinic_name": "Clínica São José",
  "unit": ""
}
```

A resposta é `201`:

```json
{
  "slug": "clinica-sao-jose",
  "loginUrl": "http://clinica-sao-jose.localhost:3000/login?cadastro=1&email=marina%40clinica.app"
}
```

- Slug repetido ou reservado (`www`, `api`, `admin`, `app`, `mail`) recebe um número no final.
- A senha precisa de ao menos 8 caracteres, com letras e números.

### `GET /api/health`

Retorna `{ "status": "ok" }`.

## Clínica (subdomínio)

### Autenticação

| Método e rota | Acesso | Corpo | Resposta |
| --- | --- | --- | --- |
| `POST /api/login` | Público, 5/min | `email`, `password` | `200` com a sessão |
| `GET /api/session` | Autenticado | | `200` com a sessão |
| `POST /api/logout` | Autenticado | | `204` |
| `POST /api/forgot-password` | Público, 5/min | `email` | `204`, sempre igual |
| `POST /api/reset-password` | Público, 5/min | `token`, `email`, `password`, `password_confirmation` | `204` |

Formato da sessão:

```json
{
  "user": { "id": "1", "name": "Dr. Carlos Mendes", "email": "demo@sorriso.app", "initials": "CM", "role": "admin", "roleLabel": "Administrador" },
  "tenant": { "slug": "sorriso-perfeito", "name": "Sorriso Perfeito", "unit": "Unidade Jardins", "initials": "SP" },
  "plan": { "name": "Pro", "trialDaysLeft": 14 }
}
```

- Login errado, e-mail inexistente e conta desativada respondem igual: `422` com `{ "message": "E-mail ou senha incorretos." }`, sem `errors`.
- Link de redefinição inválido ou expirado volta em `errors.token`.

### Clínica

| Método e rota | Acesso | Corpo |
| --- | --- | --- |
| `GET /api/clinic` | Admin | |
| `PUT /api/clinic` | Admin | `name`, `unit`, `cnpj`, `phone`, `email`, `address` |

Formato da resposta: `{ "name", "unit", "cnpj", "phone", "email", "address" }`. Os campos opcionais podem vir `null`.

### Equipe

| Método e rota | Acesso | Resposta |
| --- | --- | --- |
| `GET /api/users` | Admin | `{ "data": [usuário] }`, em ordem de nome |
| `POST /api/users` | Admin | `201` com o usuário e envio do convite por e-mail |
| `GET /api/users/{id}` | Admin | Usuário |
| `PUT /api/users/{id}` | Admin | Usuário |
| `DELETE /api/users/{id}` | Admin | `204` |

O corpo de `POST` e `PUT` tem os campos `name`, `email`, `role` (`admin`, `professional` ou `reception`), `specialty`, `attends_patients` e `active`.

Formato do usuário:

```json
{ "id": "2", "name": "Dra. Ana Beatriz", "email": "ana@sorriso.app", "initials": "AB", "role": "professional", "roleLabel": "Profissional", "specialty": "Odontologia estética", "attendsPatients": true, "active": true }
```

Regras:

- `professional` sempre atende, `reception` nunca, e só `admin` usa o valor enviado em `attends_patients`.
- Remover o próprio acesso (rebaixar, desativar ou excluir) ou o último administrador ativo dá `422` em `errors.user`.
- Usuário com agendamentos não pode ser excluído (`422` em `errors.user`). Nesse caso, desative-o.

### Agenda

`GET /api/agenda?from=2026-11-02&to=2026-11-30&professional_id=2` está disponível para todos os papéis.

- Sem `from` e `to`, o período vai da segunda-feira da semana atual até seis semanas depois. O período máximo é de 62 dias.
- `admin` e `reception` veem todos os profissionais e podem filtrar por `professional_id`. Para `professional`, o filtro enviado é ignorado e só vêm as próprias consultas.
- Cancelados e recusados não aparecem em `events`.
- `hours` são as linhas da grade, de 30 em 30 minutos, da abertura mais cedo ao fechamento mais tarde da semana.
- `procedures` traz só os ativos. `rooms` traz todas as salas, com `active`, porque consultas antigas podem estar em sala desativada.

```json
{
  "today": "2026-11-02",
  "from": "2026-11-02",
  "to": "2026-12-13",
  "hours": ["08:00", "08:30", "..."],
  "procedures": [{ "id": "3", "label": "Limpeza e prevenção", "duration": 60 }],
  "freeSlotsToday": 27,
  "professionals": [{ "id": "2", "name": "Dra. Ana Beatriz", "tone": "amber", "selected": true }],
  "rooms": [{ "id": "1", "name": "Sala 01 · Consultório", "active": true, "selected": true }],
  "events": [{
    "id": "5", "patientName": "Camila Ferreira", "date": "2026-11-03", "hour": "09:00", "endHour": "10:00",
    "duration": 60, "procedure": "Limpeza e prevenção", "procedureId": "3",
    "status": "scheduled", "statusLabel": "Agendado", "source": "internal", "professionalId": "2", "roomId": "1"
  }],
  "blocks": [{ "id": "1", "professionalId": null, "startsAt": "2026-11-16 00:00", "endsAt": "2026-11-16 23:59", "reason": "Feriado municipal" }]
}
```

### Consultas

| Método e rota | Acesso | Corpo |
| --- | --- | --- |
| `GET /api/availability?date&procedure_id&professional_id&room_id&ignore` | Autenticado | Horários livres, como `{ "slots": [{ "time": "09:00", "professionalIds": ["2"] }] }` |
| `POST /api/appointments` | Admin e recepção | `patient_id`, `professional_id`, `room_id`, `procedure_id`, `date`, `time`, `notes` |
| `GET /api/appointments/{id}` | Admin, recepção e o profissional da consulta | |
| `PUT /api/appointments/{id}` | Admin e recepção | Os mesmos campos da criação; remarca |
| `POST /api/appointments/{id}/status` | Ver a tabela de status | `status`, `reason` |
| `POST /api/appointments/{id}/accept` | Admin e recepção | `room_id` |
| `GET /api/appointment-requests` | Admin e recepção | `{ "data": [consulta] }`, pedidos Solicitados por ordem de chegada |

- Em `availability`, `ignore` é o id da consulta sendo remarcada, para ela não conflitar consigo mesma.
- `procedure_id` precisa ser de um procedimento ativo, e `room_id`, de uma sala ativa (`422` no campo).

Formato da consulta:

```json
{
  "id": "5", "status": "confirmed", "statusLabel": "Confirmado",
  "procedure": "3", "procedureLabel": "Limpeza e prevenção", "duration": 60, "priceCents": 20000,
  "date": "2026-11-03", "hour": "09:00", "endHour": "10:00",
  "source": "internal", "notes": null, "cancelReason": null, "requestedAt": "2026-11-02T10:00:00-03:00",
  "patient": { "id": "3", "name": "Camila Ferreira", "phone": "(11) 99632-4510", "email": null },
  "professional": { "id": "2", "name": "Dra. Ana Beatriz" },
  "room": { "id": "1", "name": "Sala 01 · Consultório" },
  "patientHasEmail": false,
  "notifications": [{ "type": "scheduled", "typeLabel": "Consulta agendada", "channel": "mail", "status": "skipped", "statusLabel": "Não enviado: paciente sem e-mail", "at": "..." }],
  "history": [{ "from": "scheduled", "fromLabel": "Agendado", "to": "confirmed", "toLabel": "Confirmado", "reason": null, "author": "Paula Nunes", "at": "..." }],
  "actions": ["complete", "no_show", "cancel", "reschedule"]
}
```

- `actions` lista o que o usuário logado pode fazer agora: `accept`, `reject`, `confirm`, `complete`, `no_show`, `cancel` e `reschedule`.
- `priceCents` é o preço gravado na marcação, não o preço atual do procedimento.
- `notifications` são os avisos enviados ao paciente, do mais recente ao mais antigo (ver [notificacoes.md](notificacoes.md)). `author` vem `"Paciente"` quando a mudança foi feita pelo paciente.

Status aceitos em `POST /status`:

| `status` | De | Observação |
| --- | --- | --- |
| `confirmed` | Agendado | |
| `completed`, `no_show` | Agendado, Confirmado | Só depois do início. O profissional pode, nas próprias consultas |
| `cancelled` | Solicitado, Agendado, Confirmado | Exige `reason` |
| `rejected` | Solicitado | Exige `reason` |

Para aceitar um pedido, use `/accept`. Recusas de regra de horário estão em [agenda.md](agenda.md#disponibilidade).

### Pacientes

| Método e rota | Acesso |
| --- | --- |
| `GET /api/patients?search=&page=` | Admin e recepção |
| `POST /api/patients`, `PUT /api/patients/{id}` | Admin e recepção |
| `GET /api/patients/{id}`, `DELETE /api/patients/{id}` | Admin e recepção |

- O corpo tem `name`, `phone` (com DDD), `email`, `cpf` (11 dígitos, único na clínica), `insurance` e `birth_date`.
- A listagem é paginada de 20 em 20, com `meta.total` e `meta.last_page`. A busca procura por nome, dígitos do CPF ou dígitos do telefone.
- Paciente com consultas não pode ser excluído (`422` em `errors.patient`).

### Procedimentos e salas

| Método e rota | Acesso | Corpo |
| --- | --- | --- |
| `GET /api/procedures` | Autenticado | `{ "data": [procedimento] }`, ativos primeiro e em ordem de nome |
| `POST /api/procedures`, `PUT /api/procedures/{id}` | Admin | `name`, `duration_minutes` (30 a 240, múltiplo de 30), `price_cents` (inteiro, ≥ 0), `active` |
| `GET /api/procedures/{id}` | Autenticado | |
| `DELETE /api/procedures/{id}` | Admin | `204`, ou `422` em `errors.procedure` se já foi usado |
| `GET /api/rooms` | Autenticado | `{ "data": [sala] }`, ativas primeiro e em ordem de nome |
| `POST /api/rooms`, `PUT /api/rooms/{id}` | Admin | `name`, `active` |
| `GET /api/rooms/{id}` | Autenticado | |
| `DELETE /api/rooms/{id}` | Admin | `204`, ou `422` em `errors.room` se já foi usada |

```json
{ "id": "6", "name": "Clareamento", "durationMinutes": 90, "priceCents": 45000, "active": true }
{ "id": "3", "name": "Sala 03 · Raio-X", "active": true }
```

O nome do procedimento é único entre os ativos. Regras em [agenda.md](agenda.md#procedimentos-e-salas).

### Visão geral

`GET /api/overview` é para admin e recepção; o profissional recebe `403`. O cálculo de cada número está em [agenda.md](agenda.md#visão-geral).

```json
{
  "greeting": "Boa tarde, Carlos",
  "metrics": [{ "id": "revenue", "label": "Faturamento estimado", "value": "R$ 800", "change": "12,5%", "trend": "up", "period": "vs. ontem", "icon": "billing", "tone": "green" }],
  "weeklyAppointments": { "total": 24, "change": "14,8%", "trend": "up", "comparison": "vs. semana anterior", "range": "Esta semana", "scaleMax": 10, "axis": [8, 6, 4, 2, 0], "bars": [{ "day": "Seg", "value": 6, "highlighted": true }] },
  "occupancyPercentage": 38,
  "agendaDate": "2 de novembro de 2026",
  "upcomingAppointments": [{ "id": "11", "time": "08:30", "duration": "30 min", "patientInitials": "MC", "patientName": "Mariana Costa", "patientTone": "blue", "procedure": "Avaliação", "professional": "Dr. Carlos", "status": "waiting", "statusLabel": "Confirmado" }]
}
```

- `trend` é `up`, `down` ou `flat`.
- `change` vem `"—"` quando não há base de comparação.
- A taxa de comparecimento compara em pontos percentuais (`"2,4 p.p."`).

### Expediente e bloqueios

| Método e rota | Acesso | Corpo |
| --- | --- | --- |
| `GET /api/business-hours` | Autenticado | |
| `PUT /api/business-hours` | Admin | `days`: os 7 dias com `weekday` (1 = segunda), `closed`, `opens_at`, `closes_at`, `break_starts_at`, `break_ends_at` |
| `GET /api/schedule-blocks` | Admin e recepção | Bloqueios a partir de hoje |
| `POST`, `PUT /api/schedule-blocks/{id}` | Admin e recepção | `professional_id` (vazio = clínica inteira), `starts_at` e `ends_at` (`Y-m-d H:i`), `reason` |
| `DELETE /api/schedule-blocks/{id}` | Admin e recepção | |

## Página pública (subdomínio, sem sessão)

| Método e rota | Limite | Uso |
| --- | --- | --- |
| `GET /api/public/booking` | 60/min | Clínica, procedimentos e profissionais que atendem |
| `GET /api/public/availability?date&procedure_id&professional_id` | 60/min | `{ "slots": ["09:00", "09:30"] }`, de amanhã até 60 dias |
| `POST /api/public/booking-requests` | 5/hora por IP | `name`, `phone`, `email`, `procedure_id`, `professional_id` (opcional), `date`, `time`, `notes`, `consent`, `website` |

- O pedido responde `201` com `{ "message": "Recebemos seu pedido..." }`.
- Um horário ocupado nesse meio-tempo volta como `422`, no campo do motivo.
- `booking` lista só os procedimentos ativos, sem preço.

## Links do paciente (subdomínio, sem sessão)

O token vem do link do e-mail (`/consulta?token=...`). O funcionamento está em [notificacoes.md](notificacoes.md#links-do-paciente).

| Método e rota | Limite | Efeito |
| --- | --- | --- |
| `GET /api/public/appointments/{token}` | 60/min | Resumo da consulta |
| `POST /api/public/appointments/{token}/confirm` | 10/min | Agendado → Confirmado. Se já estiver confirmada, só devolve o resumo |
| `POST /api/public/appointments/{token}/cancel` | 10/min | Agendado ou Confirmado → Cancelado; outro status dá `422` em `errors.status` |

As três rotas respondem com o resumo:

```json
{
  "clinic": { "name": "Sorriso Perfeito", "address": "Rua Oscar Freire, 1240", "phone": "(11) 3051-8090" },
  "when": "terça-feira, 03/11 às 14:00",
  "procedure": "Clareamento",
  "professional": "Dr. Carlos Mendes",
  "status": "confirmed", "statusLabel": "Confirmado",
  "canConfirm": false, "canCancel": true
}
```

Um token desconhecido, expirado, de consulta remarcada ou de outra clínica responde `404`, com `{ "message": "Este link é inválido ou expirou." }`.
