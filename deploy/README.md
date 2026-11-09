# Deploy do servidor de demonstração

O odontoApp roda numa VPS da Hostinger, atrás do Nginx Proxy Manager (NPM) que já atende outros sites. A instância é de demonstração e portfólio: os dados são apagados e recriados todo dia às 04:00.

Nada desta pasta muda o desenvolvimento local, que continua com XAMPP, `php artisan serve` e `npm run dev`.

## Como funciona

```text
Internet → NPM (80/443, certificado de odonto.<dominio> e *.odonto.<dominio>)
         → odonto-router (nginx, só na rede do NPM, sem porta pública)
              /api, /sanctum, /up → api   (Laravel: PHP-FPM + nginx)
              o resto             → web   (Nuxt, servidor Node)
  worker     php artisan queue:work      e-mails da fila
  scheduler  php artisan schedule:work   lembretes a cada 15 min e o demo:reset às 04:00
  mysql 8.4  banco central (odonto_app) e um banco por clínica (odonto_tenant_<id>)
```

Cada clínica tem um subdomínio próprio. Por isso o DNS e o certificado precisam cobrir `*.odonto.<dominio>`.

| Arquivo | Para quê |
| --- | --- |
| `api.Dockerfile` | Imagem do Laravel, usada pela API, pelo worker e pelo scheduler |
| `web.Dockerfile` | Imagem do Nuxt (`.output`) |
| `docker-compose.yml` | Serviços da VPS. As imagens vêm do GHCR |
| `docker-compose.local.yml` | Teste opcional dos contêineres na sua máquina, na porta 8088 |
| `nginx/router.conf` | Divide `/api` e `/sanctum` para o Laravel e o resto para o Nuxt |
| `mysql/init/10-clinic-grants.sh` | Na criação do volume, dá ao usuário do app os bancos `odonto_%` |
| `.env.example` | Variáveis da VPS |

## Preparação da VPS (uma vez)

### 1. DNS

No painel do domínio, crie dois registros A apontando para o IP da VPS:

- `odonto`
- `*.odonto`

### 2. Certificado wildcard no NPM

Um certificado para `*.odonto.<dominio>` só sai pelo desafio DNS:

1. Em **SSL Certificates → Add → Let's Encrypt**, informe `odonto.<dominio>` e `*.odonto.<dominio>`.
2. Ative **Use a DNS Challenge** e escolha o provedor do DNS.
3. Se a Hostinger não aparecer na lista, mova os nameservers do domínio para a Cloudflare (gratuito) e use o provedor Cloudflare, com um token que tenha permissão `Zone:DNS:Edit`.

### 3. Docker e pasta da stack

```bash
sudo mkdir -p /opt/odonto && sudo chown $USER /opt/odonto
docker network ls | grep -i proxy   # nome da rede do NPM, para NPM_NETWORK
```

Copie para `/opt/odonto` os arquivos `docker-compose.yml`, `nginx/` e `mysql/`. Depois do primeiro deploy, o GitHub Actions passa a copiá-los sozinho.

### 4. `.env`

```bash
cp .env.example /opt/odonto/.env   # e preencha
```

- Troque `odonto.example.com` pelo domínio real em todas as linhas.
- Preencha as senhas `MYSQL_ROOT_PASSWORD`, `DB_PASSWORD`, `SEED_DEMO_PASSWORD` e `PLATFORM_ADMIN_PASSWORD`. A do admin tem no mínimo 12 caracteres, com letras e números.
- Gere a `APP_KEY` com:

  ```bash
  docker run --rm ghcr.io/maiconcabral/odonto-api:latest php artisan key:generate --show
  ```

- Deixe `DB_DATABASE` começando com `odonto_`. O usuário do MySQL só tem acesso a esses bancos.

### 5. Proxy host no NPM

- **Domain names:** `odonto.<dominio>` e `*.odonto.<dominio>`.
- **Forward:** `http`, host `odonto-router`, porta `80`.
- **SSL:** o certificado wildcard, com *Force SSL* e *HTTP/2*.

### 6. Primeira subida

```bash
cd /opt/odonto
echo <token> | docker login ghcr.io -u <usuario> --password-stdin   # só se os pacotes forem privados
docker compose pull
docker compose up -d --wait
docker compose exec api php artisan demo:reset    # cria as tabelas, os planos, as clínicas demo e o admin
```

Confira em:

- `https://odonto.<dominio>`, a página pública;
- `https://sorriso-perfeito.odonto.<dominio>/login`, com `demo@sorriso.app` e a senha `SEED_DEMO_PASSWORD`;
- `https://odonto.<dominio>/admin`, com `PLATFORM_ADMIN_EMAIL`.

## GitHub Actions

| Workflow | Quando | O que faz |
| --- | --- | --- |
| `ci.yml` | Push em qualquer branch, exceto `main`, e pull requests | Pint e Pest no backend; typecheck e Vitest no frontend |
| `deploy.yml` | Push na `main` ou manual | Roda o CI. Publica `odonto-api` e `odonto-web` no GHCR com as tags `latest` e `sha-<commit>`. Copia os arquivos da stack, dá `pull` e `up` e roda `migrate` e `tenants:migrate`. Termina conferindo `/up` |

Configure em **Settings → Secrets and variables → Actions**:

- **Secrets:** `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY` (chave privada sem senha) e `VPS_PORT` (opcional, padrão 22).
- **Variable:** `DEPLOY_DOMAIN`, por exemplo `odonto.seudominio.com.br`.

O deploy usa o ambiente `demo`. Crie-o em **Settings → Environments** se quiser exigir aprovação manual.

O token do próprio workflow faz o login no GHCR durante o deploy, então a VPS não precisa guardar credenciais. Para ver as imagens sem login, torne os pacotes públicos em **Packages → Package settings**.

## Reset diário

Às 04:00 (horário de Brasília), o scheduler roda `php artisan demo:reset`, que:

1. apaga todas as clínicas, inclusive as criadas pelo cadastro público, junto com os bancos delas;
2. remove bancos de clínica que ficaram órfãos;
3. apaga os anexos (`storage/app/private/clinics`) e o cache;
4. roda `migrate:fresh --seed`.

O passo 4 recria os planos, as duas clínicas demo (com consultas relativas ao dia) e o admin da plataforma. Os dados de demonstração ficam nos seeders: `PlanSeeder`, `DemoTenantSeeder` e `PlatformAdminSeeder`.

O comando só roda com `DEMO_MODE=true`. Em outro ambiente ele se recusa, e localmente exige `--force`.

## Operação

```bash
cd /opt/odonto
docker compose ps
docker compose logs -f api worker scheduler     # logs do Laravel (LOG_CHANNEL=stderr)
docker compose exec api php artisan demo:reset  # reset na hora
docker compose exec scheduler php artisan schedule:list
```

### Voltar para uma versão anterior

```bash
sed -i 's/^IMAGE_TAG=.*/IMAGE_TAG=sha-<commit>/' .env
docker compose pull && docker compose up -d --wait
```

## Teste local dos contêineres (opcional)

Este teste não substitui o XAMPP e não usa as portas 80, 443 ou 3306.

```bash
cp deploy/.env.example deploy/.env
```

No `deploy/.env`, use:

- `localhost` no lugar de `odonto.example.com`;
- `http://` em `APP_URL`, `FRONTEND_URL` e `FRONTEND_URL_TEMPLATE`;
- `SESSION_SECURE_COOKIE=false`;
- `NUXT_PUBLIC_API_BASE=http://{host}:8088`;
- `SANCTUM_STATEFUL_DOMAINS=localhost:8088,*.localhost:8088`, porque o Sanctum compara a porta;
- `NPM_NETWORK=odonto-local-proxy`.

Depois:

```bash
docker network create odonto-local-proxy
docker compose -f deploy/docker-compose.yml -f deploy/docker-compose.local.yml --env-file deploy/.env up -d --build
docker compose -f deploy/docker-compose.yml -f deploy/docker-compose.local.yml --env-file deploy/.env exec api php artisan demo:reset
```

Abra `http://localhost:8088` e `http://sorriso-perfeito.localhost:8088/login`.
