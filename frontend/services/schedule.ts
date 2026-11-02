import type { AgendaBlock, ScheduleBlockForm } from '~/types/agenda'
import type { BusinessDay } from '~/types/schedule'

// Block form in the API field names.
function toBlockBody(form: ScheduleBlockForm) {
  return {
    professional_id: form.professionalId || null,
    starts_at: form.startsAt,
    ends_at: form.endsAt,
    reason: form.reason,
  }
}

export const scheduleService = {
  // Opening hours of the seven weekdays.
  hours: async () => (await useApi().api<{ data: BusinessDay[] }>('/api/business-hours')).data,

  // Replaces the opening hours.
  updateHours: async (days: BusinessDay[]) =>
    (await useApi().api<{ data: BusinessDay[] }>('/api/business-hours', {
      method: 'PUT',
      body: {
        days: days.map(day => ({
          weekday: day.weekday,
          closed: day.closed,
          opens_at: day.closed ? null : day.opensAt,
          closes_at: day.closed ? null : day.closesAt,
          break_starts_at: day.closed ? null : day.breakStartsAt || null,
          break_ends_at: day.closed ? null : day.breakEndsAt || null,
        })),
      },
    })).data,

  // Adds a holiday or a day off.
  createBlock: (form: ScheduleBlockForm) =>
    useApi().api<AgendaBlock>('/api/schedule-blocks', { method: 'POST', body: toBlockBody(form) }),

  // Deletes a holiday or a day off.
  removeBlock: (id: string) => useApi().api<void>(`/api/schedule-blocks/${id}`, { method: 'DELETE' }),
}
