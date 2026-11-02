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

```json
{
  "today": "2026-11-02",
  "from": "2026-11-02",
  "to": "2026-12-13",
  "hours": ["08:00", "08:30", "..."],
  "procedures": [{ "key": "cleaning", "label": "Limpeza e prevenção", "duration": 60 }],
  "freeSlotsToday": 27,
  "professionals": [{ "id": "2", "name": "Dra. Ana Beatriz", "tone": "amber", "selected": true }],
  "rooms": [{ "id": "1", "name": "Sala 01 · Consultório", "selected": true }],
  "events": [{
    "id": "5", "patientName": "Camila Ferreira", "date": "2026-11-03", "hour": "09:00", "endHour": "10:00",
    "duration": 60, "procedure": "Limpeza e prevenção", "procedureKey": "cleaning",
    "status": "scheduled", "statusLabel": "Agendado", "source": "internal", "professionalId": "2", "roomId": "1"
  }],
  "blocks": [{ "id": "1", "professionalId": null, "startsAt": "2026-11-16 00:00", "endsAt": "2026-11-16 23:59", "reason": "Feriado municipal" }]
}
```

### Consultas

| Método e rota | Acesso | Corpo |
| --- | --- | --- |
| `GET /api/availability?date&procedure&professional_id&room_id&ignore` | Autenticado | Horários livres, como `{ "slots": [{ "time": "09:00", "professionalIds": ["2"] }] }` |
| `POST /api/appointments` | Admin e recepção | `patient_id`, `professional_id`, `room_id`, `procedure`, `date`, `time`, `notes` |
| `GET /api/appointments/{id}` | Admin, recepção e o profissional da consulta | |
| `PUT /api/appointments/{id}` | Admin e recepção | Os mesmos campos da criação; remarca |
| `POST /api/appointments/{id}/status` | Ver a tabela de status | `status`, `reason` |
| `POST /api/appointments/{id}/accept` | Admin e recepção | `room_id` |
| `GET /api/appointment-requests` | Admin e recepção | `{ "data": [consulta] }`, pedidos Solicitados por ordem de chegada |

Em `availability`, `ignore` é o id da consulta sendo remarcada, para ela não conflitar consigo mesma.

Formato da consulta:

```json
{
  "id": "5", "status": "confirmed", "statusLabel": "Confirmado",
  "procedure": "cleaning", "procedureLabel": "Limpeza e prevenção", "duration": 60,
  "date": "2026-11-03", "hour": "09:00", "endHour": "10:00",
  "source": "internal", "notes": null, "cancelReason": null, "requestedAt": "2026-11-02T10:00:00-03:00",
  "patient": { "id": "3", "name": "Camila Ferreira", "phone": "(11) 99632-4510", "email": null },
  "professional": { "id": "2", "name": "Dra. Ana Beatriz" },
  "room": { "id": "1", "name": "Sala 01 · Consultório" },
  "history": [{ "from": "scheduled", "fromLabel": "Agendado", "to": "confirmed", "toLabel": "Confirmado", "reason": null, "author": "Paula Nunes", "at": "..." }],
  "actions": ["complete", "no_show", "cancel", "reschedule"]
}
```

`actions` lista o que o usuário logado pode fazer agora: `accept`, `reject`, `confirm`, `complete`, `no_show`, `cancel` e `reschedule`.

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
| `GET /api/public/availability?date&procedure&professional_id` | 60/min | `{ "slots": ["09:00", "09:30"] }`, de amanhã até 60 dias |
| `POST /api/public/booking-requests` | 5/hora por IP | `name`, `phone`, `email`, `procedure`, `professional_id` (opcional), `date`, `time`, `notes`, `consent`, `website` |

O pedido responde `201` com `{ "message": "Recebemos seu pedido..." }`. Um horário ocupado nesse meio-tempo volta como `422`, no campo do motivo.
