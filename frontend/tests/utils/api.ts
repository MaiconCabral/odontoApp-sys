import { vi } from 'vitest'

// Paths the stubbed $fetch was asked for since the test started (or since the last reset).
export function apiCalls(path?: string) {
  const paths = vi.mocked($fetch).mock.calls.map(call => String(call[0]))

  return path ? paths.filter(called => called === path) : paths
}

// Forgets the calls made so far, so a test can count only what happens next.
export function resetApiCalls() {
  vi.mocked($fetch).mockClear()
}
