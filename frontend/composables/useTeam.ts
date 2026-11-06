// Loads the clinic team.
export function useTeam() {
  return useCachedAsyncData('team', () => usersService.list())
}
