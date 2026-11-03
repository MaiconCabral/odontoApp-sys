import type { StaffNotification, StaffNotificationList } from '~/types/notifications'

type NotificationsResponse = { data: StaffNotification[], meta: { unreadCount: number } }

export const notificationsService = {
  // Latest alerts of the signed-in user and how many are unread.
  list: async (): Promise<StaffNotificationList> => {
    const response = await useApi().api<NotificationsResponse>('/api/notifications')

    return { items: response.data, unreadCount: response.meta.unreadCount }
  },

  // Marks one alert as read.
  markRead: (id: string) => useApi().api(`/api/notifications/${id}/read`, { method: 'POST' }),

  // Marks every alert as read.
  markAllRead: () => useApi().api('/api/notifications/read-all', { method: 'POST' }),
}
