export function useTheme() {
  const isDark = useState('theme-dark', () => false)

  function toggleTheme() {
    isDark.value = !isDark.value
  }

  return { isDark: readonly(isDark), toggleTheme }
}
