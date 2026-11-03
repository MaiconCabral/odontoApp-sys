// Procedures of the clinic, active and inactive.
export function useProcedures() {
  return useAsyncData('procedures', () => proceduresService.list())
}

// Rooms of the clinic, active and inactive.
export function useRooms() {
  return useAsyncData('rooms', () => roomsService.list())
}
