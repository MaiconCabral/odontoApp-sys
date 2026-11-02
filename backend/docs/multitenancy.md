# Multi-tenancy e autenticação

Cada clínica tem banco próprio e é identificada pelo subdomínio, com `stancl/tenancy` 3.

## Bancos

| Banco | Conteúdo | Migrations |
| --- | --- | --- |
| Central (`odonto_app`) | `tenants`, `domains`, `plans`, `cache`, `jobs` | `database/migrations/` |
| Clínica (`odonto_tenant_<id>`) | `users`, `password_reset_tokens`, `rooms`, `patients`, `appointments` | `database/migrations/tenant/` |

- Migration nova de dado da clínica vai em `database/migrations/tenant/` e roda com `php artisan tenants:migrate`.
- Models centrais usam o trait `CentralConnection`. Os demais seguem o banco da clínica atual.
- O cadastro cria o banco, então o usuário do MySQL em produção precisa de permissão `CREATE DATABASE`.

## Requisições

| Host | Rotas |
| --- | --- |
| `localhost` (central) | `routes/api.php`: cadastro de clínica e health check |
| `<slug>.localhost` (clínica) | `routes/tenant.php`: sessão, clínica, usuários e agenda |

Rotas de clínica recusam o domínio central com 404. Subdomínio desconhecido responde "Clínica não encontrada", também com 404.

## Sessão e isolamento

A autenticação usa a sessão SPA do Sanctum, sem tokens de API. Três regras sustentam o isolamento, e cada uma tem teste em `tests/Feature/TenantIsolationTest.php`:

1. **O tenancy começa antes do grupo `api`.** O Sanctum carrega o usuário da sessão, e esse usuário está no banco da clínica.
2. **`AuthenticateTenantSession` confere a clínica da sessão antes de carregar o usuário.** Ids de usuário se repetem entre bancos: sem isso, a sessão do usuário 1 da clínica A procuraria o usuário 1 da clínica B.
3. **O cookie fica preso ao host,** com `SESSION_DOMAIN=null`. Não defina um domínio pai.

## Papéis

| Papel | Agenda | Pacientes (mock) | Usuários e clínica |
| --- | --- | --- | --- |
| `admin` | Todos | Sim | Sim |
| `reception` | Todos | Sim | Não |
| `professional` | Só a própria | Não | Não |

- As regras ficam nas policies de `app/Domains/*/Policies`, registradas no `AppServiceProvider`.
- Em `GET /api/agenda`, o papel `professional` ignora qualquer `professional_id` enviado.
- `attends_patients` indica quem aparece na agenda. `professional` sempre atende, `reception` nunca, e `admin` escolhe.
- Ninguém remove o próprio acesso, e a clínica sempre mantém um administrador ativo (`AdminGuard`).

## Dados de demonstração

`php artisan migrate:fresh --seed` cria as clínicas `sorriso-perfeito` e `clinica-horizonte`, apenas no ambiente `local`. A senha vem de `SEED_DEMO_PASSWORD`.

| Conta | Papel |
| --- | --- |
| `demo@sorriso.app` | Admin que atende |
| `ana@sorriso.app`, `felipe@sorriso.app` | Profissional |
| `recepcao@sorriso.app` | Recepção |
| `helena@horizonte.app` | Admin da outra clínica |
| `tiago@horizonte.app` | Profissional da outra clínica |

`migrate:fresh` não apaga bancos de clínicas criadas pelo cadastro, que ficam órfãos. Antes de rodá-lo, remova essas clínicas com `$tenant->delete()`. O seeder só recria as duas clínicas demo.

## Restrições enquanto não houver Redis

A extensão `phpredis` não está instalada no ambiente local:

- `CacheTenancyBootstrapper` está desligado, porque exige cache com tags. Toda chave de cache com dado de clínica precisa incluir o id do tenant, como já fazem os limites de tentativa.
- A sessão usa o driver `file`.
- Ao adotar o driver `database` na fila, aponte a conexão para o banco central. Dentro de uma requisição de clínica, a conexão padrão é a do tenant, que não tem a tabela `jobs`.

## Produção

Ajuste no ambiente:

- `TENANCY_CENTRAL_DOMAINS`: o domínio central primeiro, pois ele monta os links enviados por e-mail.
- `FRONTEND_URL_TEMPLATE`, como `https://{host}`.
- `SANCTUM_STATEFUL_DOMAINS` e `CORS_ALLOWED_ORIGINS_PATTERN`, com o domínio real.
- `SESSION_SECURE_COOKIE=true`.

`SEED_DEMO_PASSWORD` não deve existir em produção.

## Testes

`phpunit.xml` usa SQLite: o central em `:memory:` e cada clínica em um arquivo temporário em `database/`, removido ao fim do teste. Os testes nunca tocam em `odonto_app`.

Use `clinicRequest()` e `centralRequest()`, de `tests/TestCase.php`, que enviam a requisição como o SPA. Os testes dividem um único processo, então esses helpers limpam o tenancy, os guards e o broker de senha entre as chamadas. O middleware de CSRF é ignorado nos testes, portanto mudanças nesse fluxo precisam ser conferidas com o servidor no ar.
