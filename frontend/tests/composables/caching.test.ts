import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h } from 'vue'
import BlockModal from '~/components/agenda/BlockModal.vue'
import DetailsModal from '~/components/appointments/DetailsModal.vue'
import AppointmentModal from '~/components/appointments/NewModal.vue'
import WaitlistEntryModal from '~/components/waitlist/EntryModal.vue'
import AdminClinicPage from '~/pages/admin/clinicas/[id].vue'
import AdminClinicsPage from '~/pages/admin/index.vue'
import AgendaPage from '~/pages/app/agenda.vue'
import { appointmentDetails } from '../fixtures/agenda'
import { apiCalls, resetApiCalls } from '../utils/api'

// Component that only loads the procedures, like a settings card.
const ProceduresConsumer = defineComponent({
  async setup() {
    const { data } = await useProcedures()

    return () => h('p', `${data.value?.length ?? 0} procedimentos`)
  },
})

// Two cards on the same screen asking for the same data.
const TwoConsumers = defineComponent({
  setup: () => () => h('div', [h(ProceduresConsumer), h(ProceduresConsumer)]),
})

describe('cached data', () => {
  it('asks once when two parts of the screen need the same list', async () => {
    const page = await mountSuspended(TwoConsumers)
    await flushPromises()

    expect(page.text()).toContain('procedimentos')
    expect(apiCalls('/api/procedures')).toHaveLength(1)
  })

  it('reuses the list when the screen opens again', async () => {
    const first = await mountSuspended(ProceduresConsumer)
    await flushPromises()
    first.unmount()

    await mountSuspended(ProceduresConsumer)
    await flushPromises()

    expect(apiCalls('/api/procedures')).toHaveLength(1)
  })

  it('still reloads on refresh', async () => {
    await mountSuspended(ProceduresConsumer)
    await flushPromises()

    await refreshNuxtData('procedures')

    expect(apiCalls('/api/procedures')).toHaveLength(2)
  })

  it('loads again after the data is dropped', async () => {
    const first = await mountSuspended(ProceduresConsumer)
    await flushPromises()
    first.unmount()

    clearNuxtData('procedures')
    await mountSuspended(ProceduresConsumer)
    await flushPromises()

    expect(apiCalls('/api/procedures')).toHaveLength(2)
  })
})

describe('form options', () => {
  beforeEach(() => {
    vi.spyOn(appointmentsService, 'get').mockResolvedValue(appointmentDetails)
  })

  it.each([
    ['new appointment', 'appointment', AppointmentModal, {}],
    ['appointment details', 'appointment-details', DetailsModal, { id: 'event-2' }],
    ['schedule block', 'block', BlockModal, {}],
  ] as const)('opens the %s form without loading the agenda', async (_name, modal, component, payload) => {
    useActiveModal().openModal(modal, payload)

    await mountSuspended(component)
    await flushPromises()

    expect(apiCalls('/api/agenda')).toHaveLength(0)
    expect(apiCalls('/api/agenda/options')).toHaveLength(1)
  })

  it('opens the waitlist form without loading the agenda', async () => {
    await mountSuspended(WaitlistEntryModal, { props: { entry: null } })
    await flushPromises()

    expect(apiCalls('/api/agenda')).toHaveLength(0)
    expect(apiCalls('/api/agenda/options')).toHaveLength(1)
  })

  it('loads the options once for many forms', async () => {
    useActiveModal().openModal('appointment')
    ;(await mountSuspended(AppointmentModal)).unmount()
    useActiveModal().openModal('block')
    await mountSuspended(BlockModal)
    await flushPromises()

    expect(apiCalls('/api/agenda/options')).toHaveLength(1)
  })

  it('takes the options from the agenda already on screen', async () => {
    await mountSuspended(AgendaPage)
    await flushPromises()
    resetApiCalls()

    useActiveModal().openModal('appointment')
    await mountSuspended(AppointmentModal)
    await flushPromises()

    expect(apiCalls('/api/agenda')).toHaveLength(0)
    expect(apiCalls('/api/agenda/options')).toHaveLength(0)
  })

  it('asks again after the catalog changes', async () => {
    useActiveModal().openModal('appointment')
    ;(await mountSuspended(AppointmentModal)).unmount()

    forgetAgendaOptions()
    await mountSuspended(AppointmentModal)
    await flushPromises()

    expect(apiCalls('/api/agenda/options')).toHaveLength(2)
  })

  it('loads the appointment and the options at the same time', async () => {
    let releaseDetails: (value: typeof appointmentDetails) => void = () => {}
    vi.spyOn(appointmentsService, 'get').mockReturnValue(new Promise((resolve) => {
      releaseDetails = resolve
    }))
    useActiveModal().openModal('appointment-details', { id: 'event-2' })

    const mounting = mountSuspended(DetailsModal)
    await flushPromises()

    // The details are still on their way, and the options were already asked for.
    expect(apiCalls('/api/agenda/options')).toHaveLength(1)

    releaseDetails(appointmentDetails)
    expect((await mounting).text()).toContain('Camila Ferreira')
  })
})

describe('agenda requests', () => {
  it('asks for the waiting requests once when the menu and the panel need them together', async () => {
    // The layout badge and the agenda panel mount on the same screen.
    const RequestsConsumer = defineComponent({
      async setup() {
        const { data } = await useAppointmentRequests()

        return () => h('p', `${data.value?.length ?? 0} pedidos`)
      },
    })

    await mountSuspended(defineComponent({ setup: () => () => h('div', [h(RequestsConsumer), h(RequestsConsumer)]) }))
    await flushPromises()

    expect(apiCalls('/api/appointment-requests')).toHaveLength(1)
  })
})

describe('platform plans', () => {
  it('reuses the plans between the clinics list and a clinic', async () => {
    usePlatformSession().admin.value = { id: '1', name: 'Equipe', email: 'equipe@plataforma.app' }

    ;(await mountSuspended(AdminClinicsPage)).unmount()
    await mountSuspended(AdminClinicPage, { route: '/admin/clinicas/c1' })
    await flushPromises()

    expect(apiCalls('/api/platform/plans')).toHaveLength(1)
    expect(apiCalls('/api/platform/clinics')).toHaveLength(1)
    expect(apiCalls('/api/platform/clinics/c1')).toHaveLength(1)
  })
})
