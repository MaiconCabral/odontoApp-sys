# Documentação do backend

API do odontoApp em Laravel 12, consumida pelo Nuxt em `frontend/`.

| Documento | Conteúdo |
| --- | --- |
| [API](api.md) | Endpoints, contratos de requisição e resposta, erros |
| [Multi-tenancy e autenticação](multitenancy.md) | Banco por clínica, subdomínios, sessão, papéis, contas demo e produção |
| [Agenda](agenda.md) | Disponibilidade, procedimentos e salas, travas contra horário duplicado, status, pedido online e visão geral |
| [Notificações](notificacoes.md) | E-mails ao paciente, fila, lembrete de 24h, links de confirmação, WhatsApp e como rodar |

## O que está implementado

- **Etapa 1:** autenticação, clínicas (tenants), usuários e agenda somente leitura.
- **Etapa 2:** agenda completa, pacientes e pedidos online.
- **Etapa 3:** notificações e lembrete ao paciente, cadastro de procedimentos e salas, visão geral real.

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
- **Agenda:**
  - consultas marcadas, remarcadas e com status (Solicitado, Agendado, Confirmado, Concluído, Faltou, Cancelado, Recusado), com histórico de cada mudança;
  - expediente por dia da semana, com almoço, e bloqueios para feriados e folgas;
  - travas no banco contra dois agendamentos no mesmo horário;
  - `GET /api/agenda` devolve o período pedido (até 62 dias).
- **Pedidos online:** o paciente pede um horário pela página pública da clínica, sem login, e a recepção aceita ou recusa.
- **Pacientes:** CRUD com busca por nome, CPF ou telefone e paginação.
- **Procedimentos e salas:**
  - cadastrados pelo admin, com duração, preço e situação;
  - a consulta guarda o preço do momento da marcação.
- **Notificações:**
  - e-mail em fila a cada pedido, marcação, remarcação, cancelamento e recusa;
  - lembrete 24h antes, com links para o paciente confirmar ou cancelar sem login;
  - cada envio fica registrado na consulta;
  - cada clínica pode desligar o lembrete em Configurações → Clínica.
- **Avisos para a equipe (sino):** novo pedido, cancelamento e confirmação pelo paciente e falha no lembrete. Ficam na tabela `notifications` da clínica, um aviso por usuário. Detalhes em [notificacoes.md](notificacoes.md#avisos-para-a-equipe-sino).
- **Preferências da clínica:** lembrete de 24h, confirmação automática e lista de espera. O lembrete e a lista de espera mudam o comportamento; a confirmação automática só é salva. Detalhes em [notificacoes.md](notificacoes.md#preferências-da-clínica).
- **Área clínica:** ficha do paciente com as consultas, prontuário (anotações só de acréscimo), odontograma, anexos em disco privado por clínica e lista de espera com aviso por e-mail. Detalhes em [prontuario.md](prontuario.md).
- **Financeiro:** pagamentos lançados na consulta (parciais, com estorno), "Recebido hoje" na visão geral e relatórios por dia, profissional ou procedimento. Detalhes em [financeiro.md](financeiro.md).
- **Painel da plataforma:** administradores próprios no domínio central (`/admin`), lista de clínicas com uso, troca de plano, fim do trial, suspensão e cadastro de planos. Detalhes em [plataforma.md](plataforma.md).
- **Limite do plano:** `max_professionals` é aplicado ao criar ou ativar profissionais ([api.md](api.md#equipe)).
- **Assinatura:** `GET /api/clinic/subscription` devolve o plano atual, a situação (`trial`, `active` ou `expired`, calculada por `trial_ends_at`) e os planos disponíveis. Ainda não há cobrança.
- **Visão geral:** consultas, faturamento estimado, comparecimento, novos pacientes, semana, ocupação e próximos pacientes, calculados do banco.
- **Seeds:** criam os planos e duas clínicas de demonstração.

Ficam para as próximas etapas:

- canal de WhatsApp (o ponto de extensão já existe);
- faturamento com Stripe;

## Estrutura

O código segue o padrão de `.cursor/rules/backend.md`, organizado por domínio:

```
app/
├── Domains/
│   ├── Tenancy/   Tenant, Plan, cadastro e edição da clínica, ClinicUrl
│   ├── Identity/  User, UserRole, CRUD da equipe, AdminGuard, e-mail de senha
│   ├── Agenda/    Consultas, pacientes, procedimentos, salas, expediente, bloqueios, DaySchedule, OverviewQuery
│   └── Notifications/  PatientNotifier, AppointmentMessage, NotificationLog, lembretes, links do paciente
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
composer dev                       # API, fila, agendador e Vite juntos
php artisan queue:work --tries=3   # só a fila (envia os e-mails)
php artisan schedule:work          # só o agendador (lembrete a cada 15 min)
php artisan appointments:send-reminders  # envia os lembretes devidos agora
php artisan test                   # testes (SQLite, nunca o MySQL do .env)
php vendor/bin/pint --test         # estilo
```
