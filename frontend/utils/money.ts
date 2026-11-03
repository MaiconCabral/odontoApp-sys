// Price as "R$ 450,00".
export function formatMoney(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

// Cents of a price typed as "450", "450,5" or "1.250,00". Null when it is not a price.
export function toCents(value: string): number | null {
  const normalized = value.trim().replace(/^R\$\s*/, '').replace(/\./g, '').replace(',', '.')

  if (!/^\d+(\.\d{1,2})?$/.test(normalized)) {
    return null
  }

  return Math.round(Number(normalized) * 100)
}

// Price as typed in the form, without the currency sign.
export function centsToInput(cents: number): string {
  return (cents / 100).toFixed(2).replace('.', ',')
}
