export type ModalName = 'appointment' | 'appointment-details' | 'block' | 'patient'

// Which modal is open, with the data it needs.
export function useActiveModal() {
  const activeModal = useState<ModalName | null>('active-modal', () => null)
  const modalPayload = useState<Record<string, unknown> | null>('active-modal-payload', () => null)

  // Opens a modal, replacing the one already open.
  function openModal(name: ModalName, payload: Record<string, unknown> | null = null) {
    modalPayload.value = payload
    activeModal.value = name
  }

  // Closes the open modal.
  function closeModal() {
    activeModal.value = null
    modalPayload.value = null
  }

  return { activeModal, modalPayload, openModal, closeModal }
}
