import { describe, expect, it } from 'vitest'
import { createLatestGuard } from '~/utils/latest'

describe('createLatestGuard', () => {
  it('keeps only the newest call current', () => {
    const guard = createLatestGuard()
    const first = guard.start()
    const second = guard.start()

    expect(first()).toBe(false)
    expect(second()).toBe(true)
  })

  it('makes every call stale on cancel', () => {
    const guard = createLatestGuard()
    const call = guard.start()

    guard.cancel()

    expect(call()).toBe(false)
  })

  it('drops an older answer that arrives after a newer one', async () => {
    const guard = createLatestGuard()
    const shown: string[] = []
    let releaseSlow: (value: string) => void = () => {}

    // Loads like a search box: only the current call writes to the screen.
    const search = async (answer: Promise<string>) => {
      const isCurrent = guard.start()
      const value = await answer

      if (isCurrent()) {
        shown.push(value)
      }
    }

    const slow = search(new Promise((resolve) => {
      releaseSlow = resolve
    }))
    await search(Promise.resolve('Camila'))
    releaseSlow('Ca')
    await slow

    expect(shown).toEqual(['Camila'])
  })
})
