import type { ProcedureForm, ProcedureItem, RoomForm, RoomItem } from '~/types/catalog'

// Procedure form in the API field names.
function toProcedureBody(form: ProcedureForm) {
  return {
    name: form.name,
    duration_minutes: form.durationMinutes,
    price_cents: toCents(form.price) ?? -1,
    active: form.active,
  }
}

export const proceduresService = {
  // Every procedure, active ones first.
  list: async () => (await useApi().api<{ data: ProcedureItem[] }>('/api/procedures')).data,

  // Adds a procedure.
  create: (form: ProcedureForm) =>
    useApi().api<ProcedureItem>('/api/procedures', { method: 'POST', body: toProcedureBody(form) }),

  // Updates a procedure. Booked appointments keep their length and price.
  update: (id: string, form: ProcedureForm) =>
    useApi().api<ProcedureItem>(`/api/procedures/${id}`, { method: 'PUT', body: toProcedureBody(form) }),

  // Deletes a procedure that was never booked.
  remove: (id: string) => useApi().api<void>(`/api/procedures/${id}`, { method: 'DELETE' }),
}

export const roomsService = {
  // Every room, active ones first.
  list: async () => (await useApi().api<{ data: RoomItem[] }>('/api/rooms')).data,

  // Adds a room.
  create: (form: RoomForm) => useApi().api<RoomItem>('/api/rooms', { method: 'POST', body: form }),

  // Updates a room.
  update: (id: string, form: RoomForm) => useApi().api<RoomItem>(`/api/rooms/${id}`, { method: 'PUT', body: form }),

  // Deletes a room that was never used.
  remove: (id: string) => useApi().api<void>(`/api/rooms/${id}`, { method: 'DELETE' }),
}
