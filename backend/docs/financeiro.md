# Financeiro

Pagamentos lançados pela recepção e relatórios por período. Não há cobrança online. O código está em `app/Domains/Finance`, e a tabela `payments` fica no banco de cada clínica (migration `tenant/2026_11_06_000000_create_payments_table.php`).

## Pagamentos

| Método e rota | Acesso | Corpo |
| --- | --- | --- |
| `GET /api/appointments/{id}/payments` | Admin e recepção | |
| `POST /api/appointments/{id}/payments` | Admin e recepção | `amount_cents`, `method`, `paid_at`, `notes` e `allow_over` |
| `POST /api/payments/{id}/void` | Admin | `reason` |

- **Formas (`PaymentMethod`):** `pix`, `credit_card`, `debit_card`, `cash`, `insurance` e `other`.
- **Regras (`RecordPayment`):**
  - o valor é maior que zero, e pagamento parcial é aceito;
  - `paid_at` não pode estar no futuro; sem ele, vale hoje;
  - a soma dos pagamentos válidos não passa de `price_cents`, a não ser com `allow_over` (procedimento extra no dia). Senão, a resposta é `422` com "Faltam R$ X";
  - consultas solicitadas, canceladas ou recusadas não recebem pagamento;
  - a consulta é travada (`lockForUpdate`) durante o lançamento, para que dois lançamentos ao mesmo tempo não passem do preço.
- **Estorno (`VoidPayment`):** nada é apagado. O estorno grava `voided_at`, `voided_by` e `void_reason`, e o pagamento deixa de contar. Estornar duas vezes dá `422`.
- **Resposta das três rotas:** `{ priceCents, paidCents, balanceCents, paymentStatus, payments: [{ id, amountCents, method, methodLabel, paidAt, notes, recordedBy, voided, voidReason, voidedBy, canVoid }] }`.

### Situação de pagamento da consulta

`Appointment::paymentStatus()`:

- `none`: consulta sem preço;
- `pending`: nada pago;
- `partial`: pago em parte;
- `paid`: pago por inteiro.

A situação aparece:

- na consulta (`paidCents`, `balanceCents` e `paymentStatus` em `AppointmentResource`);
- nos eventos da agenda (`paymentStatus`);
- nas consultas da ficha do paciente.

Listas usam o escopo `withPaidCents()`, que soma os pagamentos numa só consulta SQL.

### Visão geral

O cartão de dinheiro passou a ser **"Recebido hoje"**: a soma dos pagamentos válidos com `paid_at` de hoje, comparada com a de ontem. O valor estimado pelo preço das consultas do dia continua no campo `detail` ("Previsto R$ X").

## Relatórios

`GET /api/reports?from=2026-11-01&to=2026-11-30&group=professional`, só para admin.

- **Agrupamentos:** `day`, `professional` ou `procedure`. O período vai até 366 dias; acima disso, a resposta é `422` em `errors.to`.
- **Cada linha:**
  - `appointments`: agendadas, confirmadas, concluídas e faltas;
  - `completed` e `noShow`;
  - `absenceRate`: faltas sobre concluídas mais faltas, ou `null`;
  - `expectedCents`: preço das agendadas, confirmadas e concluídas;
  - `receivedCents`.
- **Datas:**
  - o previsto segue a data da consulta;
  - o recebido segue a data do pagamento (regime de caixa). Agrupado por profissional ou procedimento, ele vai para a consulta paga;
  - por isso, um dia pode ter dinheiro recebido sem consultas.
- **Ordem:** por dia, em ordem de data; nos outros agrupamentos, do maior previsto para o menor.
- **`totals`:** a soma das linhas.
- **`byMethod`:** o recebido no período por forma de pagamento.
- **Consultas:** a query (`ReportQuery`) faz três consultas, qualquer que seja o tamanho do período.

O cast `date` grava `paid_at` com hora (`2026-11-10 00:00:00`). Por isso, as consultas comparam o dia com `whereDate` e `DATE()`, e não com `whereBetween`.
