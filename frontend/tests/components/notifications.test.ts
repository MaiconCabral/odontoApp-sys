import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import NotificationsMenu from '~/components/layout/NotificationsMenu.vue'
import { notificationsFixture } from '../fixtures/settings'

// Menus of earlier tests keep their timers and listeners until unmounted.
const mounted: VueWrapper[] = []

afterEach(() => {
  mounted.splice(0).forEach(menu => menu.unmount())
})

const mountMenu = async () => {
  const menu = await mountSuspended(NotificationsMenu, { props: { buttonClass: '' } })

  mounted.push(menu)

  await flushPromises()

  return menu
}

describe('NotificationsMenu', () => {
  it('shows the red dot only while there are unread alerts', async () => {
    const menu = await mountMenu()

    expect(menu.find('[data-testid="unread-dot"]').exists()).toBe(true)
    expect(menu.find('button').attributes('aria-label')).toBe('Notificações, 1 não lida(s)')

    useNotificationsState().value = { items: [], unreadCount: 0 }
    await nextTick()

    expect(menu.find('[data-testid="unread-dot"]').exists()).toBe(false)
  })

  it('lists the alerts when the bell is opened', async () => {
    const menu = await mountMenu()
    const bell = menu.find('button')

    expect(bell.attributes('aria-expanded')).toBe('false')

    await bell.trigger('click')
    await flushPromises()

    expect(bell.attributes('aria-expanded')).toBe('true')
    expect(menu.text()).toContain('Novo pedido de agendamento')
    expect(menu.text()).toContain('Paciente confirmou a consulta')
  })

  it('shows an empty state', async () => {
    vi.spyOn(notificationsService, 'list').mockResolvedValue({ items: [], unreadCount: 0 })
    const menu = await mountMenu()

    await menu.find('button').trigger('click')
    await flushPromises()

    expect(menu.text()).toContain('Nenhuma notificação por enquanto.')
    expect(menu.text()).not.toContain('Marcar todas como lidas')
  })

  it('marks an alert as read and opens its appointment', async () => {
    const markRead = vi.spyOn(notificationsService, 'markRead').mockResolvedValue(undefined)
    const menu = await mountMenu()

    await menu.find('button').trigger('click')
    await flushPromises()
    await menu.findAll('li button').find(item => item.text().includes('Novo pedido'))?.trigger('click')

    expect(markRead).toHaveBeenCalledWith('n1')
    expect(useNotificationsState().value.unreadCount).toBe(0)
    expect(useActiveModal().activeModal.value).toBe('appointment-details')
    expect(useActiveModal().modalPayload.value).toEqual({ id: notificationsFixture.data[0]?.appointmentId })
    expect(menu.find('[data-testid="unread-dot"]').exists()).toBe(false)
  })

  it('marks every alert as read', async () => {
    const markAllRead = vi.spyOn(notificationsService, 'markAllRead').mockResolvedValue(undefined)
    const menu = await mountMenu()

    await menu.find('button').trigger('click')
    await flushPromises()
    await menu.findAll('button').find(item => item.text() === 'Marcar todas como lidas')?.trigger('click')

    expect(markAllRead).toHaveBeenCalled()
    expect(useNotificationsState().value.items.every(item => item.read)).toBe(true)
  })
})

describe('NotificationsMenu polling', () => {
  // Pretends the tab went to the background or came back.
  function setVisibility(state: 'visible' | 'hidden') {
    Object.defineProperty(document, 'visibilityState', { value: state, configurable: true })
    document.dispatchEvent(new Event('visibilitychange'))
  }

  afterEach(() => {
    setVisibility('visible')
    vi.useRealTimers()
  })

  it('polls every minute while the tab is visible', async () => {
    vi.useFakeTimers({ toFake: ['setInterval', 'clearInterval', 'Date'] })
    const list = vi.spyOn(notificationsService, 'list')
    await mountMenu()

    expect(list).toHaveBeenCalledTimes(1)

    vi.advanceTimersByTime(60_000)
    expect(list).toHaveBeenCalledTimes(2)
  })

  it('does not poll while the tab is in the background, and catches up on return', async () => {
    vi.useFakeTimers({ toFake: ['setInterval', 'clearInterval', 'Date'] })
    const list = vi.spyOn(notificationsService, 'list')
    await mountMenu()

    setVisibility('hidden')
    vi.advanceTimersByTime(180_000)
    expect(list).toHaveBeenCalledTimes(1)

    setVisibility('visible')
    expect(list).toHaveBeenCalledTimes(2)
  })

  it('does not ask again on return when the last load is recent', async () => {
    vi.useFakeTimers({ toFake: ['setInterval', 'clearInterval', 'Date'] })
    const list = vi.spyOn(notificationsService, 'list')
    await mountMenu()

    setVisibility('hidden')
    vi.advanceTimersByTime(5_000)
    setVisibility('visible')

    expect(list).toHaveBeenCalledTimes(1)
  })

  // Clicks with fake timers get dropped by Vue (see docs/testes.md), so only Date.now moves here.
  it('opens with the list just loaded instead of asking again', async () => {
    const now = vi.spyOn(Date, 'now').mockReturnValue(1_000_000)
    const list = vi.spyOn(notificationsService, 'list')
    const menu = await mountMenu()

    await menu.find('button').trigger('click')
    expect(list).toHaveBeenCalledTimes(1)

    // Closed and opened again later, it refreshes.
    await menu.find('button').trigger('click')
    now.mockReturnValue(1_030_000)
    await menu.find('button').trigger('click')
    expect(list).toHaveBeenCalledTimes(2)
  })

  it('stops polling when the top bar leaves', async () => {
    vi.useFakeTimers({ toFake: ['setInterval', 'clearInterval', 'Date'] })
    const list = vi.spyOn(notificationsService, 'list')
    await mountMenu()

    mounted.splice(0).forEach(menu => menu.unmount())
    vi.advanceTimersByTime(120_000)
    setVisibility('visible')

    expect(list).toHaveBeenCalledTimes(1)
  })
})
