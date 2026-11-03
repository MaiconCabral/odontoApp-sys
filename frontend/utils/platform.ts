import type { PlatformClinicStatus } from '~/types/platform'

// Status of a clinic in the global panel.
export const CLINIC_STATUS_LABELS: Record<PlatformClinicStatus, string> = {
  trial: 'Trial',
  active: 'Ativa',
  expired: 'Trial expirado',
  suspended: 'Suspensa',
}

// Badge colors of each status, from the theme tones.
export const CLINIC_STATUS_CLASSES: Record<PlatformClinicStatus, string> = {
  trial: 'bg-brand-soft text-brand-text',
  active: 'bg-tone-green-bg text-tone-green-text',
  expired: 'bg-tone-amber-bg text-tone-amber-text',
  suspended: 'bg-tone-red-bg text-tone-red-text',
}

// Where the panel goes after the login: only paths of the panel, so a link cannot send the admin elsewhere.
export function safePanelRedirect(redirect: unknown): string {
  return typeof redirect === 'string' && /^\/admin(\/|$|\?)/.test(redirect) ? redirect : '/admin'
}
