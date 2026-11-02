// Number of requests waiting, shown next to Agenda in the menu.
export function usePendingRequestsCount() {
  return useState<number>('pending-requests', () => 0)
}

// Patient requests waiting for the reception.
export function useAppointmentRequests() {
  const count = usePendingRequestsCount()

  return useAsyncData('appointment-requests', async () => {
    const requests = await appointmentsService.requests()

    count.value = requests.length

    return requests
  })
}
