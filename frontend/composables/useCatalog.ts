// Procedures of the clinic, active and inactive.
export function useProcedures() {
  return useCachedAsyncData('procedures', () => proceduresService.list())
}

// Rooms of the clinic, active and inactive.
export function useRooms() {
  return useCachedAsyncData('rooms', () => roomsService.list())
}
