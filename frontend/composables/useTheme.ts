export function useTheme() {
  const colorMode = useColorMode()
  const isDark = computed(() => colorMode.value === 'dark')

  function toggleTheme() {
    colorMode.preference = isDark.value ? 'light' : 'dark'
  }

  return { isDark, toggleTheme }
}
