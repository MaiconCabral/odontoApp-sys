import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import NotificationsMenu from '~/components/layout/NotificationsMenu.vue'
import { notificationsFixture } from '../fixtures/settings'

const mountMenu = async () => {
  const menu = await mountSuspended(NotificationsMenu, { props: { buttonClass: '' } })

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
