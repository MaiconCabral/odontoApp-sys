export type ModalName = 'appointment' | 'patient'

export function useActiveModal() {
  const activeModal = useState<ModalName | null>('active-modal', () => null)

  function openModal(name: ModalName) {
    activeModal.value = name
  }

  function closeModal() {
    activeModal.value = null
  }

  return { activeModal, openModal, closeModal }
}
