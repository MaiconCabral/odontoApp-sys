# Painel da plataforma

Área da equipe que administra todas as clínicas e os planos. Fica no domínio central, em `http://localhost:3000/admin`, e não em um subdomínio: o código leria `admin.localhost` como uma clínica, e o slug `admin` já é reservado em `RegisterClinic`.

## Acesso

- **Contas:**
  - os administradores da plataforma ficam na tabela central `platform_admins` (model `App\Domains\Platform\Models\PlatformAdmin`, com `CentralConnection`);
  - não são usuários de nenhuma clínica, e não há cadastro público.
- **Guard `platform`:** sessão própria, com o provider `platform_admins`, em `config/auth.php`.
  - Um usuário de clínica com o mesmo e-mail e a mesma senha não entra no painel, e a sessão do painel não abre rotas de clínica.
- **Criar ou trocar a senha:**

  ```bash
  php artisan platform:create-admin equipe@exemplo.com --name="Equipe"
  # pede a senha sem mostrar; mínimo de 12 caracteres, com letras e números
  ```

  Rodar de novo com o mesmo e-mail troca a senha.
- **Sessão no domínio central:** `AuthenticateTenantSession` não carrega usuário de clínica fora de um subdomínio. Antes, um cookie de sessão de clínica enviado ao domínio central fazia o Sanctum procurar a tabela `users` no banco central e responder `500`.

## Rotas (domínio central, prefixo `/api/platform`)

| Método e rota | Acesso | Corpo ou query |
| --- | --- | --- |
| `POST login` | Público, 5 por minuto | `email`, `password` |
| `GET session`, `POST logout` | Admin da plataforma | |
| `GET clinics` | Admin da plataforma | `search`, `plan_id`, `status` (`trial`, `active`, `expired`, `suspended`) e `page` |
| `GET clinics/{id}`, `PUT clinics/{id}` | Admin da plataforma | `plan_id`, `trial_ends_at` (`Y-m-d` ou vazio) e `suspended` |
| `GET plans`, `POST plans`, `PUT plans/{id}`, `DELETE plans/{id}` | Admin da plataforma | `code`, `name`, `price_cents` e `max_professionals` (vazio é sem limite) |

- **Lista de clínicas:**
  - mais nova primeiro, de 20 em 20;
  - a busca procura em nome, e-mail e endereço (subdomínio);
  - cada clínica traz `usage` (`activeUsers`, `professionals` e `appointmentsLast30Days`), lido no banco da clínica só para as clínicas da página.
- **Situação:** `suspended` vem antes. Fora isso: `trial` enquanto `trial_ends_at` está no futuro, `expired` depois dele, e `active` sem data (clínica pagante).
- **Planos:**
  - a listagem traz quantas clínicas usam cada plano;
  - o código aceita só letras minúsculas, números e hífen, e é único;
  - um plano com clínicas não pode ser excluído;
  - o plano de trial (`pro`) não pode ser excluído nem ter o código alterado;
  - baixar `max_professionals` vale só para novos profissionais ([api.md](api.md#equipe)).

## Suspensão

`PUT clinics/{id}` com `suspended: true` grava `suspended_at` na coluna `data` do tenant. O middleware `EnsureClinicIsActive`, no grupo de rotas de clínica, responde `403` com `{ "message": "Clínica suspensa. Fale com o suporte.", "suspended": true }` para:

- login, recuperação de senha e todas as rotas com sessão;
- a página pública de agendamento.

Os links do paciente (`public/appointments/{token}`) continuam abertos, para que os pacientes ainda confirmem ou cancelem o que já está marcado. Reativar (`suspended: false`) apaga `suspended_at`.
