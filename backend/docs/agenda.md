# Agenda

Como a API decide se um horário está livre, como o status de uma consulta muda, como chega o pedido do paciente e como a visão geral é calculada. Os endpoints estão em [api.md](api.md), e os avisos ao paciente, em [notificacoes.md](notificacoes.md).

## Disponibilidade

Toda regra de horário passa por `DaySchedule` (`app/Domains/Agenda/Support/DaySchedule.php`). Ela carrega o dia com três consultas (expediente, bloqueios e consultas que ocupam horário) e responde, por campo, por que um intervalo não pode ser marcado. A agenda interna, a página pública e as actions usam a mesma classe.

| Regra | Campo do erro | Mensagem |
| --- | --- | --- |
| Início fora da grade de 30 minutos | `starts_at` | Escolha um horário em intervalos de 30 minutos. |
| Horário no passado | `starts_at` | Escolha um horário futuro. |
| Dia sem atendimento | `starts_at` | A clínica não atende neste dia. |
| Antes da abertura ou depois do fechamento | `starts_at` | Fora do horário de funcionamento da clínica. |
| Cruza o almoço | `starts_at` | Este horário cai no intervalo de almoço da clínica. |
| Bloqueio da clínica (feriado) | `starts_at` | A clínica está fechada neste horário: *motivo*. |
| Folga do profissional | `professional_id` | O profissional não atende neste horário: *motivo*. |
| Profissional já ocupado | `professional_id` | Este profissional já tem consulta neste horário. |
| Sala já ocupada | `room_id` | Esta sala já está ocupada neste horário. |

- A duração vem do procedimento escolhido (ver [Procedimentos e salas](#procedimentos-e-salas)). O horário só é livre se a duração inteira couber.
- Ocupam horário os status Solicitado, Agendado e Confirmado. Cancelado, Recusado, Concluído e Faltou liberam o horário.
- Consultas que só se encostam (uma termina às 10:00 e a outra começa às 10:00) não conflitam.
- Mudar o expediente ou criar um bloqueio não desmarca consultas já marcadas. A recepção decide o que fazer com elas.

### Expediente

A migration da agenda cria o expediente padrão em todo banco de clínica, inclusive nas cadastradas depois:

- segunda a sexta, 08:00 às 18:00, com almoço das 12:00 às 13:00;
- sábado, 08:00 às 12:00;
- domingo fechado.

Só o admin muda o expediente, em Configurações → Horários.

### Bloqueios

Feriados (sem profissional) e folgas (com profissional) ficam em `schedule_blocks`, com início e fim em data e hora. Admin e recepção criam e removem bloqueios pela agenda, em "Bloquear horário".

## Procedimentos e salas

Os dois ficam no banco da clínica e são cadastrados pelo admin em Configurações → Procedimentos e Configurações → Salas.

- **Procedimento (`procedures`):**
  - campos: nome, duração e preço (`price_cents`, em centavos);
  - a duração vai de 30 a 240 minutos, em múltiplos de 30, para caber na grade;
  - o nome é único entre os ativos;
  - o preço pode ser zero, como no retorno.
- **Sala (`rooms`):** nome e situação (`active`).
- **Inativos:**
  - não aparecem para novas marcações, nem na agenda interna nem na página pública;
  - continuam aparecendo nas consultas antigas;
  - aceitar um pedido também exige uma sala ativa.
- **Exclusão:** um procedimento ou sala já usado em consultas não pode ser excluído e responde `422`, com a sugestão de desativar.
- **Preço da consulta:**
  - a consulta guarda em `appointments.price_cents` o preço vigente na marcação;
  - mudar o preço do procedimento depois não altera consultas já marcadas;
  - remarcar mantém o preço, a não ser que o procedimento mude;
  - a página pública não mostra preço.
- **Clínicas já existentes:** a migration `2026_11_03_000000_create_procedures_table` criou os cinco procedimentos do antigo enum (Avaliação, Avaliação ortodôntica, Limpeza e prevenção, Restauração estética e Consulta de retorno), com preço zero. Ela converteu a coluna texto `appointments.procedure` em `procedure_id`. As seeds de demonstração dão preço a esses procedimentos.

## Travas contra horário duplicado

Toda action que reserva horário roda dentro de `DB::transaction`:

1. `AvailabilityChecker::lock()` trava com `lockForUpdate()` a linha do profissional e a da sala.
2. Só depois `AvailabilityChecker::ensure()` relê o dia e confere as regras.
3. A consulta é gravada e a transação termina, liberando a trava.

Assim, dois pedidos simultâneos para o mesmo profissional ou a mesma sala esperam em fila, e o segundo encontra o horário já ocupado. No pedido sem profissional definido, os candidatos são travados sempre na ordem dos ids, para evitar deadlock.

Isso foi conferido no MySQL com dois processos PHP disputando o mesmo horário: o segundo esperou o primeiro terminar e recebeu "Este profissional já tem consulta neste horário". Os testes automatizados rodam em SQLite, que não reproduz o lock, então essa conferência é manual.

## Status

```
Solicitado ──aceitar──▶ Agendado ──confirmar──▶ Confirmado ──▶ Concluído
     │                     │   └──────────────────────────────▶ Faltou
     └──recusar──▶ Recusado └──cancelar──▶ Cancelado ◀──cancelar── Confirmado
```

| Ação | Admin | Recepção | Profissional |
| --- | --- | --- | --- |
| Marcar, remarcar, aceitar, recusar, confirmar, cancelar | Sim | Sim | Não |
| Concluída, Faltou | Sim | Sim | Só nas próprias consultas |

- As transições ficam em `AppointmentStatus::nextStatuses()`. Uma transição fora do fluxo responde `422` em `errors.status`.
- **Aceitar** um pedido exige escolher uma sala livre (`POST /appointments/{id}/accept`); não é uma mudança de status comum.
- **Recusar** e **cancelar** exigem motivo, gravado em `cancel_reason`.
- **Concluída** e **Faltou** só valem depois do início da consulta.
- **Remarcar** confere a disponibilidade de novo, sem contar a própria consulta, e volta para Agendado se estava Confirmada.
- Toda mudança, inclusive a criação, grava uma linha em `appointment_status_changes`, com status anterior, novo status, motivo e autor. O autor fica vazio quando é o paciente (pedido online ou link do e-mail) e aparece como "Paciente".
- O paciente também confirma ou cancela pelo link do e-mail; ver [notificacoes.md](notificacoes.md#links-do-paciente).
- A resposta da API traz em `actions` o que o usuário logado pode fazer agora. O frontend só mostra esses botões.

## Pedido online do paciente

A página `/agendar` do subdomínio da clínica não pede login e usa as rotas `api/public/*`.

1. O paciente escolhe o procedimento e, se quiser, o profissional.
2. Escolhe um dia entre amanhã e daqui a 60 dias e um dos horários livres.
3. Informa nome, telefone, e-mail opcional e autoriza o contato.

A API então:

- localiza o paciente pelo telefone (só dígitos) ou pelo e-mail e cria um novo se não encontrar (`FindOrCreatePatient`). A resposta é sempre a mesma, para não revelar se o paciente já existia;
- escolhe um profissional livre quando o paciente não tem preferência;
- grava a consulta como Solicitada, sem sala. O horário fica ocupado até a recepção responder;
- recebe no máximo 5 pedidos por hora por IP e clínica;
- responde igual a um pedido de robô (campo oculto `website` preenchido), mas não grava nada.

O pedido aparece no painel "Pedidos de agendamento" da agenda, e o número de pedidos pendentes aparece ao lado de Agenda no menu.

## Visão geral

`OverviewQuery` monta o painel inicial (`GET /api/overview`) para admin e recepção. O profissional recebe `403`.

| Número | Cálculo |
| --- | --- |
| Consultas hoje | Agendadas, confirmadas e concluídas de hoje, comparadas com ontem |
| Faturamento estimado | Soma de `price_cents` dessas mesmas consultas (sem pedidos, canceladas, recusadas e faltas), comparada com ontem |
| Taxa de comparecimento | Concluídas ÷ (concluídas + faltas) no mês, comparada com o mês anterior em pontos percentuais |
| Novos pacientes | Cadastros no mês, comparados com o mês anterior |
| Semana | Agendadas, confirmadas e concluídas de segunda a domingo, comparadas com a semana anterior, com o dia de hoje destacado |
| Ocupação | Minutos marcados (inclusive faltas) ÷ minutos de atendimento dos profissionais ativos hoje, sem almoço e bloqueios (`DaySchedule::serves`) |
| Próximos pacientes | Consultas de hoje, as já encerradas no fim, com o status da clínica (Faltou aparece como encerrada) |

A semana começa na segunda-feira, inclusive com o locale pt_BR, em que o Carbon começaria no domingo.

## Ainda não implementado

- WhatsApp: o ponto de extensão está descrito em [notificacoes.md](notificacoes.md#como-ligar-o-whatsapp).
- Teste automatizado de concorrência em MySQL.
