# Área clínica

Ficha do paciente, prontuário, odontograma, anexos e lista de espera. O código está em `app/Domains/Clinical` (prontuário, odontograma e anexos) e em `app/Domains/Agenda` (lista de espera). As tabelas ficam no banco de cada clínica (migration `tenant/2026_11_05_000000_create_clinical_tables.php`).

## Quem acessa

Prontuário, odontograma e anexos são dados de saúde (LGPD). As regras estão em `PatientPolicy`:

| Papel | Ficha e consultas | Prontuário, odontograma e anexos | Excluir anexo |
| --- | --- | --- | --- |
| Admin | Todos os pacientes | Todos os pacientes | Sim |
| Recepção | Todos os pacientes | Não | Não |
| Profissional | Só pacientes com consulta com ele | Só pacientes com consulta com ele | Não |

- `view`: a ficha e as consultas.
- `viewRecord` e `writeRecord`: os dados clínicos.
- `deleteAttachment`: excluir um anexo.
- As observações do cadastro (`patients.notes`) aparecem para a recepção. Dado clínico vai no prontuário.

## Prontuário

| Método e rota | Corpo |
| --- | --- |
| `GET /api/patients/{id}/notes` | |
| `POST /api/patients/{id}/notes` | `body` (até 5.000), `appointment_id` e `amends_id`, ambos opcionais |

- **Só acréscimo:** uma anotação nunca é editada nem excluída, nem pela API nem pelo banco (as chaves usam `restrictOnDelete`).
- **Correção:** é uma nova anotação com `amends_id` apontando para a anterior. As duas continuam no histórico.
- **Vínculos:** `appointment_id` e `amends_id` precisam ser do mesmo paciente. Senão, a resposta é `422`.
- **Resposta:** `{ id, body, author, appointment: { id, date, procedureLabel } | null, amendsId, createdAt }`, da mais recente para a mais antiga.

## Odontograma

| Método e rota | Corpo |
| --- | --- |
| `GET /api/patients/{id}/odontogram` | |
| `POST /api/patients/{id}/odontogram` | `tooth`, `condition`, `surface` e `notes` |

- **Dentes:** numeração FDI. Permanentes de 11 a 48 e decíduos de 51 a 85 (`Clinical\Support\Teeth`).
- **Faces:** `M` (mesial), `D` (distal), `O` (oclusal ou incisal), `V` (vestibular) e `L` (lingual ou palatina).
- **Situações (`ToothCondition`):** `healthy`, `caries`, `restoration`, `root_canal`, `crown`, `implant`, `extraction_needed` e `missing`.
  - Cárie e restauração exigem a face.
  - Endodontia, coroa, implante, extração indicada e ausente valem para o dente inteiro e são gravadas sem face, mesmo que uma seja enviada.
- **Histórico:** cada `POST` é uma nova linha de `tooth_records`. O estado atual é a última linha de cada dente e face (`scopeCurrent`).
- **Resposta:** `{ current: [...], history: [...] }`. `history` traz as últimas 100 mudanças.

## Anexos

| Método e rota | Acesso |
| --- | --- |
| `GET /api/patients/{id}/attachments` | `viewRecord` |
| `POST /api/patients/{id}/attachments` (multipart, campo `file`) | `writeRecord`, até 20 envios por minuto por usuário |
| `GET /api/attachments/{id}/download` | `viewRecord` |
| `DELETE /api/attachments/{id}` | Admin |

- **Tipos aceitos:** PDF, JPG, PNG e WebP, até 10 MB. A regra `mimes` confere o conteúdo do arquivo, não só a extensão.
- **Onde fica o arquivo:** no disco `local` (privado, `storage/app/private`), em `clinics/{id da clínica}/patients/{id do paciente}/`, com nome aleatório.
  - O caminho leva o id da clínica de forma explícita. O `FilesystemTenancyBootstrapper` do stancl não foi ligado, porque também mudaria o `storage_path()` e o `asset()` de toda requisição de clínica, e isso afeta sessões e e-mails.
  - Se a gravação no banco falhar, o arquivo é apagado.
- **Download:** só pela rota, com sessão, `Content-Type` gravado no envio e `X-Content-Type-Options: nosniff`. O caminho no disco nunca sai na resposta.
- **Exclusão:** apaga o arquivo e a linha.

## Lista de espera

| Método e rota | Acesso |
| --- | --- |
| `GET /api/waitlist?status=&patient_id=` | Admin e recepção |
| `POST /api/waitlist` | Admin e recepção |
| `PUT /api/waitlist/{id}` | Admin e recepção |
| `DELETE /api/waitlist/{id}` | Admin e recepção |

- **Corpo:** `patient_id`, `procedure_id` (ativo), `professional_id` (opcional; vazio aceita qualquer um), `from_date` (de hoje em diante), `to_date` (até 180 dias depois de `from_date`), `period` (`morning`, `afternoon` ou `any`) e `notes`. Só o `PUT` aceita `status`.
- **Situações:** `waiting`, `notified`, `booked` e `removed`. Sem `status`, a listagem traz as abertas (`waiting` e `notified`), da mais antiga para a mais nova. Voltar para `waiting` permite uma nova oferta.

### Oferta de horário vago

```
ChangeAppointmentStatus → cancelada ou recusada ─┐
RescheduleAppointment → mudou horário ou dentista ┤
                                                   → OfferFreedSlotJob (fila, depois do commit)
                                                   → OfferFreedSlot
```

1. Só roda se a preferência `waiting_list` da clínica estiver ligada e o horário ainda for futuro.
2. Busca as entradas `waiting` que atendem a todas estas condições:
   - o dia está dentro de `from_date` e `to_date`;
   - o período combina (manhã é antes de 12h);
   - o profissional é o mesmo, ou não há preferência;
   - o procedimento cabe na duração liberada;
   - o paciente não é o da consulta que saiu e tem e-mail.
3. Confere que o horário continua livre para o procedimento da entrada (`DaySchedule::problems`).
4. Avisa as 3 entradas mais antigas (`OfferFreedSlot::MAX_OFFERS`):
   - cada uma recebe o e-mail `WaitlistOffer`, com o link `/agendar?date=&time=&professional_id=&procedure_id=` no site da clínica;
   - cada entrada passa para `notified`, com `notified_at`;
   - a equipe recebe o aviso `waitlist_offered` no sino.
5. Quem pedir primeiro fica com o horário. O pedido online já trata a disputa, e os outros recebem "Este horário acabou de ser reservado".

Quando o paciente consegue o horário, a recepção marca a entrada como `booked`. Isso não é automático.
