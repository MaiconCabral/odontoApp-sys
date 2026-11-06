// Guards against late answers: when a search or a slot list is asked again before the first
// answer arrives, only the newest answer may update the screen.
export function createLatestGuard() {
  let current = 0

  return {
    // Marks a new call and returns a check that stays true while no newer call has started.
    start() {
      const mine = ++current

      return () => mine === current
    },
    // Makes every call still on its way stale, for example when the form closes.
    cancel() {
      current++
    },
  }
}
