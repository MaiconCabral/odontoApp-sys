import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import { STATUS_CLASSES } from '~/utils/appointments'

// Status colors must come from tokens, so they follow the light and the dark theme.
describe('theme tokens', () => {
  it('builds the status classes only from tokens', () => {
    for (const classes of Object.values(STATUS_CLASSES)) {
      expect(classes).not.toMatch(/#[0-9a-f]{3,6}/i)
    }
  })

  it('defines every tone for both themes', () => {
    const css = readFileSync('assets/css/main.css', 'utf8')
    const [light = '', dark = ''] = css.split('.dark {')

    for (const tone of ['green', 'amber', 'red', 'violet', 'pink']) {
      for (const kind of ['bg', 'text', 'line', 'solid']) {
        expect(light).toContain(`--tone-${tone}-${kind}:`)
        expect(dark).toContain(`--tone-${tone}-${kind}:`)
      }
    }
  })
})
