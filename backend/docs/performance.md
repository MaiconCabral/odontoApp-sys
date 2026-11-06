# Performance

Levantamento dos gargalos da API, com o que já foi corrigido e o que ficou para depois. O lado do frontend está em [`frontend/docs/performance.md`](../../frontend/docs/performance.md).

## Corrigido

| Problema | Correção | Teste |
| --- | --- | --- |
| A lista de pedidos rodava 3 SUM por linha (`paidCents()` e `paymentStatus()` sem `withPaidCents`) | `AppointmentController::requests` usa `withPaidCents()`. Detalhe, criação, edição, status e aceite carregam a soma uma vez com `loadPaidCents()` | `QueryBudgetTest` |
| O resumo de pagamentos somava de novo no banco o que já tinha carregado | `PaymentController::summary` soma a coleção carregada | `QueryBudgetTest` |
| `scopeOverlapping` não tinha limite inferior: toda checagem de horário percorria o índice desde a primeira consulta | Limite `starts_at > início - Appointment::MAX_MINUTES` (24 h; procedimentos vão até 4 h) | `PerformanceGuardsTest` |
| `whereDate()` em `paid_at`, `from_date` e `to_date` impedia o uso dos índices | Novo escopo `Payment::paidBetween()`, com intervalo semiaberto. `OfferFreedSlot` também passou a usar intervalo | `PerformanceGuardsTest`, `FinanceTest`, `OverviewTest` |
| Faltavam índices para o sino, a fila de pedidos e a lista de pacientes | Migration `2026_11_07_000000_add_performance_indexes`: `notifications(notifiable_type, notifiable_id, read_at)` e `(…, created_at)`, `appointments(status, created_at)`, `patients(name)` | — |
| A grade de horários da agenda lia o expediente em duas consultas (MIN e MAX) | Uma consulta com `MIN` e `MAX` juntos | `AgendaTest` |
| Anotações e anexos do prontuário vinham sem limite | Páginas de 30 (`meta.last_page`). Os anexos não carregam mais o paciente por linha | `PerformanceGuardsTest` |
| Relações carregadas inteiras (usuário com hash de senha, paciente com observações) | Colunas limitadas nos `with()` de consultas, prontuário, anexos e lista de espera | `QueryBudgetTest` |
| A clínica era buscada no banco central a cada requisição | Cache do `DomainTenantResolver` (5 min), limpo quando a clínica ou o domínio mudam | `PerformanceGuardsTest` |
| `Procedure::options()` consultava a cada carga da agenda e do `/agendar` | Cache por clínica (`tenant:{id}:procedure-options`), limpo ao salvar ou excluir um procedimento | `PerformanceGuardsTest` |
| Os modais carregavam a agenda inteira só para preencher os selects | Novo `GET /api/agenda/options` (procedimentos, profissionais e salas, sem eventos) | `PerformanceGuardsTest` |

### Como os testes protegem

- `TestCase::countQueries()` conta as consultas de uma requisição, em qualquer conexão. `TestCase::recordQueries()` devolve o SQL de cada uma.
- `tests/Feature/QueryBudgetTest.php` compara cada listagem com 1 e com 6 linhas. Se o número de consultas sobe com as linhas, há N+1.
- Antes de medir, uma requisição de aquecimento preenche os caches, para as duas contagens partirem do mesmo estado.

## Pendente

| Problema | Onde | Por que ficou |
| --- | --- | --- |
| A lista da plataforma abre o banco de até 20 clínicas por página (3 COUNT cada) | `PlatformClinicController.php:48,90-96` | Precisa de contadores guardados no banco central ou de cache com invalidação por evento |
| Os lembretes fazem um UPDATE por consulta e percorrem as clínicas em sequência | `SendDueReminders.php:34-41`, `SendAppointmentReminders.php` | Precisa de uma fila por clínica e de UPDATE em lote |
| O sino grava um INSERT por destinatário dentro da requisição | `StaffNotifier.php:38` | Pode ir para a fila quando houver Redis |
| A criação da clínica roda as migrations dentro do `POST /clinics` | `TenancyServiceProvider.php:40` (`shouldBeQueued(false)`) | Muda o fluxo do cadastro: a tela precisa esperar o banco ficar pronto |
| A busca de pacientes usa `like '%termo%'` e `replace()` no CPF | `Patient.php:101-105` | Precisa de uma coluna de CPF só com dígitos ou de índice full-text |
| A lista de pacientes devolve `notes` | `PatientResource.php:36` | O botão de editar da tabela abre o formulário com a linha. Sem `notes`, salvar apagaria as observações. Antes é preciso o formulário buscar o paciente completo |
| A lista de espera e os pedidos não têm paginação | `WaitlistController::index`, `AppointmentController::requests` | São filas de itens em aberto, que ficam pequenas. As colunas carregadas já foram limitadas |
| Mover uma consulta na agenda faz GET antes do PUT | `frontend/components/agenda/Board.vue:63` | O evento da agenda não tem `patientId` nem `notes`, e o PUT exige os dois |
| Sem compressão nem `Cache-Control` | `public/.htaccess` | Configuração do servidor de produção |
| Sem conexões persistentes ao MySQL | `config/database.php` | Avaliar junto com o servidor de produção |
| Fila e cache em `database` e `file` | `.env` | Aguardam o Redis. Com ele, ligar o `CacheTenancyBootstrapper` e tirar o id da clínica das chaves |
