import type { StaffNotificationList } from '~/types/notifications'

// Bell without alerts.
const emptyList = (): StaffNotificationList => ({ items: [], unreadCount: 0 })

// Alerts of the signed-in user, shared by the bell and reset on logout.
export function useNotificationsState() {
  return useState<StaffNotificationList>('staff-notifications', emptyList)
}

// Loads the alerts and marks them as read.
export function useNotifications() {
  const state = useNotificationsState()

  // Fetches the latest alerts and the unread count.
  async function load() {
    try {
      state.value = await notificationsService.list()
    }
    catch {
      // The bell keeps the last alerts; the next poll tries again.
    }
  }

  // Marks one alert as read right away; reloads the list if the API refuses.
  async function markRead(id: string) {
    const item = state.value.items.find(notification => notification.id === id)

    if (!item || item.read) {
      return
    }

    item.read = true
    state.value.unreadCount = Math.max(0, state.value.unreadCount - 1)
    await notificationsService.markRead(id).catch(() => load())
  }

  // Marks every alert as read right away; reloads the list if the API refuses.
  async function markAllRead() {
    state.value.items.forEach((notification) => {
      notification.read = true
    })
    state.value.unreadCount = 0
    await notificationsService.markAllRead().catch(() => load())
  }

  // Empties the bell, used on logout and between tests.
  function reset() {
    state.value = emptyList()
  }

  return { state, load, markRead, markAllRead, reset }
}
