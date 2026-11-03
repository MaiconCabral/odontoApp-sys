export type StaffNotificationType = 'booking_requested' | 'cancelled_by_patient' | 'confirmed_by_patient' | 'reminder_failed'

export type StaffNotification = {
  id: string
  type: StaffNotificationType
  title: string
  body: string
  appointmentId: string | null
  date: string | null
  read: boolean
  createdAt: string
}

export type StaffNotificationList = {
  items: StaffNotification[]
  unreadCount: number
}
