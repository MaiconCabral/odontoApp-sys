import type { ClinicData } from '~/types/settings'

type ClinicResponse = { [Field in keyof ClinicData]: string | null }

// Turns empty API fields into empty strings.
function toClinicData(clinic: ClinicResponse): ClinicData {
  return {
    name: clinic.name ?? '',
    unit: clinic.unit ?? '',
    cnpj: clinic.cnpj ?? '',
    phone: clinic.phone ?? '',
    email: clinic.email ?? '',
    address: clinic.address ?? '',
  }
}

export const clinicService = {
  // Clinic data.
  get: async () => toClinicData(await useApi().api<ClinicResponse>('/api/clinic')),

  // Saves the clinic data.
  update: async (clinic: ClinicData) =>
    toClinicData(await useApi().api<ClinicResponse>('/api/clinic', { method: 'PUT', body: clinic })),
}
