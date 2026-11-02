# Documentação do backend

API do odontoApp em Laravel 12, consumida pelo Nuxt em `frontend/`.

| Documento | Conteúdo |
| --- | --- |
| [API](api.md) | Endpoints, contratos de requisição e resposta, erros |
| [Multi-tenancy e autenticação](multitenancy.md) | Banco por clínica, subdomínios, sessão, papéis, contas demo e produção |

## O que está implementado

Etapa 1: autenticação, clínicas (tenants), usuários e agenda somente leitura.

- **Clínicas:**
  - Cada clínica tem banco MySQL próprio e um subdomínio, com `stancl/tenancy` 3.
  - O cadastro público cria a clínica, o banco e o administrador, com trial Pro de 14 dias.
  - O administrador vê e edita os dados da própria clínica.
- **Autenticação:**
  - Login, logout e sessão com a sessão SPA do Sanctum, por cookie e sem tokens.
  - Recuperação de senha por link enviado por e-mail.
  - Limite de tentativas no cadastro, no login e nas rotas de senha.
- **Usuários:**
  - CRUD da equipe, restrito ao administrador. Quem é criado recebe um link para definir a própria senha.
  - Ninguém remove o próprio acesso, e a clínica sempre mantém um administrador ativo.
- **Papéis:**
  - `admin`, `reception` e `professional`, aplicados por policies.
  - O papel `professional` vê só a própria agenda, filtrada pela API.
- **Agenda:** `GET /api/agenda` monta salas, profissionais e agendamentos no formato que o frontend usa.
- **Seeds:** criam os planos e duas clínicas de demonstração.

Ficam para as próximas etapas:

- criação, reagendamento e cancelamento de consultas, com trava anti-double-booking;
- notificações em fila;
- faturamento com Stripe;
- gestão global de clínicas e de planos.

## Estrutura

O código segue o padrão de `.cursor/rules/backend.md`, organizado por domínio:

```
app/
├── Domains/
│   ├── Tenancy/   Tenant, Plan, cadastro e edição da clínica, ClinicUrl
│   ├── Identity/  User, UserRole, CRUD da equipe, AdminGuard, e-mail de senha
│   └── Agenda/    Room, Patient, Appointment, AgendaQuery
├── Http/
│   ├── Controllers/Api/  controllers finos
│   ├── Requests/         validação (FormRequest) e conversão para DTO
│   ├── Resources/        formato das respostas
│   └── Middleware/       AuthenticateTenantSession, EnsureUserIsActive
└── Support/Initials.php
```

Dentro de cada domínio:

| Pasta | Papel |
| --- | --- |
| `Models/` | Eloquent. Os models centrais usam `CentralConnection` |
| `Actions/` | Uma regra de negócio por classe, com o método `handle` |
| `DTOs/` | Dados de entrada `readonly`, montados pelo FormRequest (`toData()`) |
| `Policies/` | Quem pode o quê, registradas no `AppServiceProvider` |
| `Queries/` | Leituras com regra, como a agenda visível a cada papel |
| `Support/` | Peças reutilizadas pelo domínio |

## Como uma requisição percorre o código

```
Rota (routes/tenant.php ou routes/api.php)
  → tenancy pelo subdomínio → sessão → AuthenticateTenantSession → auth:sanctum
  → FormRequest: authorize() + rules() + toData()
  → Controller → Action, Query ou Policy
  → Resource → JSON
```

## Como adicionar uma funcionalidade

1. Para dados da clínica, crie a migration em `database/migrations/tenant/`. A migration central só serve para dados compartilhados entre clínicas.
2. Crie o model, a action e o DTO no domínio certo.
3. Valide no FormRequest, com mensagens em português iguais às do frontend.
4. Autorize com uma policy e registre-a no `AppServiceProvider`.
5. Exponha a rota em `routes/tenant.php`, dentro do grupo autenticado.
6. Escreva testes com Pest: feature para o endpoint, incluindo cada papel e o isolamento entre clínicas, e unit para a regra de negócio. Use os helpers `createClinic()`, `clinicRequest()` e `centralRequest()` de `tests/TestCase.php`.

## Comandos

```bash
php artisan migrate:fresh --seed   # recria o central e as clínicas demo (apaga dados)
php artisan tenants:migrate        # roda as migrations das clínicas
php artisan tenants:list           # lista as clínicas e os subdomínios
php artisan test                   # testes (SQLite, nunca o MySQL do .env)
php vendor/bin/pint --test         # estilo
```
