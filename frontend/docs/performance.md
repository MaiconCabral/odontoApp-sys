# Performance

Levantamento dos gargalos do frontend, com o que já foi corrigido e o que ficou para depois. O lado da API está em [`backend/docs/performance.md`](../../backend/docs/performance.md).

## Dados entre telas

As telas do painel rodam só no navegador (`ssr: false`). Nesse modo, o `useAsyncData` do Nuxt 3 só reaproveita dados durante a hidratação. Por isso, toda montagem e todo segundo componente com a mesma chave pediam os dados à API de novo.

`useCachedAsyncData(key, handler)`, em `composables/useCachedAsyncData.ts`:

- reaproveita o que já foi carregado para a chave;
- junta chamadas simultâneas num só pedido (`dedupe: 'defer'`);
- `refresh()` e `refreshNuxtData(key)` continuam indo à API.

Usam: `useProcedures`, `useRooms`, `useTeam`, `useSettings`, `useBusinessHours` e `usePlatformPlans`.

Para dados alterados em outra tela, use `clearNuxtData(key)`. Uma chave que nenhum componente mostra não escuta `refreshNuxtData`.

## Corrigido

| Problema | Correção | Teste |
| --- | --- | --- |
| Os modais de consulta, bloqueio e lista de espera carregavam a agenda inteira só para preencher os selects | `useAgendaOptions()` reaproveita a agenda já carregada ou chama `GET /api/agenda/options`. `forgetAgendaOptions()` limpa depois de mudar procedimentos, salas ou equipe | `tests/composables/caching.test.ts` |
| O detalhe da consulta carregava as opções e depois a consulta, em sequência | `Promise.all` | `caching.test.ts` |
| O layout e o painel da agenda pediam os pedidos ao mesmo tempo | `dedupe: 'defer'` em `useAppointmentRequests` | `caching.test.ts` |
| Os planos da plataforma eram pedidos em cada página do admin, em sequência com a lista | `usePlatformPlans()` com cache e `Promise.all` | `caching.test.ts` |
| Salvar procedimento ou sala recarregava a agenda inteira | Recarrega só a própria lista e limpa as opções | `caching.test.ts` |
| Uma resposta atrasada da busca de pacientes ou dos horários livres podia sobrescrever a mais nova | `createLatestGuard()` (`utils/latest.ts`) no `NewModal`, no `EntryModal` e no `/agendar`. Os timers são limpos ao fechar | `tests/utils/latest.test.ts`, `tests/components/modals.test.ts` |
| O sino consultava a cada minuto mesmo com a aba em segundo plano, e de novo ao abrir | Pausa com a aba oculta e recupera ao voltar. Ao abrir, só pede de novo se a última carga tem mais de 10 s | `tests/components/notifications.test.ts` |
| O odontograma filtrava todos os registros cerca de 290 vezes por render | `toothStates()` agrupa uma vez, e um `computed` guarda o que cada dente desenha | `tests/components/odontogram.test.ts`, `tests/utils/odontogram.test.ts` |
| O FullCalendar recebia um array de eventos novo a cada troca de visão ou de data | Eventos num `computed` separado; `initialView` e `initialDate` só na montagem | `tests/pages/agenda*.test.ts` |
| Prontuário e anexos sem limite | Páginas da API com "Carregar mais", em `usePagedList()` | `tests/components/patientPages.test.ts` |

## Pendente

| Problema | Onde |
| --- | --- |
| FullCalendar entra no chunk da página da agenda. Pode ser carregado sob demanda, já que fica dentro de `<ClientOnly>` | `components/agenda/Calendar.vue` |
| ApexCharts é grande para três gráficos pequenos | `components/app/Chart.vue` |
| Google Fonts bloqueia a renderização (2 famílias, 7 pesos) | `nuxt.config.ts:14-17` |
| Modais carregados de forma estática | `pages/app/lista-espera.vue`, `pages/app/pacientes/[id].vue`, cards de configurações |
| O gráfico remonta inteiro ao trocar o tema | `components/app/Chart.vue:58` |
| Formatadores de data criados por item a cada render | `DetailsModal.vue`, `AttachmentsTab.vue`, `OdontogramTab.vue` |
| A busca nos modais pede uma página de 20 pacientes e mostra 6 | A API ainda não aceita `per_page` |
| As datas do relatório disparam uma busca a cada mudança | `pages/app/relatorios.vue`. Um seletor de data muda uma vez por escolha, então o ganho é pequeno |
