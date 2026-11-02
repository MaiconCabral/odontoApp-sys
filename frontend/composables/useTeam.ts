// Loads the clinic team.
export function useTeam() {
  return useAsyncData('team', () => usersService.list())
}
