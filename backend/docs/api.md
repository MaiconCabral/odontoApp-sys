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

`GET /api/agenda?professional_id=2` está disponível para todos os papéis.

```json
{
  "today": "2026-11-02",
  "hours": ["08:00", "09:00", "..."],
  "procedures": ["Avaliação", "..."],
  "freeSlotsToday": 27,
  "professionals": [{ "id": "2", "name": "Dra. Ana Beatriz", "tone": "amber", "selected": true }],
  "rooms": [{ "id": "1", "name": "Sala 01 · Consultório", "selected": true }],
  "events": [{ "id": "5", "patientName": "Camila Ferreira", "date": "2026-11-02", "hour": "09:00", "procedure": "Avaliação", "tone": "blue", "professionalId": "2", "roomId": "1" }]
}
```

- Os eventos cobrem de 1 mês antes a 2 meses depois de hoje e não incluem cancelados.
- `admin` e `reception` veem todos os profissionais e podem filtrar por `professional_id`.
- `professional` recebe só a própria agenda. O `professional_id` enviado é ignorado.
