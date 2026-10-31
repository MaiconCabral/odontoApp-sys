export function useToast() {
  const message = useState<string | null>('toast-message', () => null)

  function showToast(text: string) {
    message.value = text
  }

  function hideToast() {
    message.value = null
  }

  return { message, showToast, hideToast }
}
