import { describe, expect, it } from 'vitest'
import {
  parseApiError,
  safeRedirect,
  tenantPath,
  validateForgotPassword,
  validateLogin,
  validateNewPassword,
  validateRegister,
} from '~/utils/auth'

const validRegister = {
  name: 'Ana Souza',
  email: 'ana@clinica.com',
  password: 'senhaSegura1',
  passwordConfirmation: 'senhaSegura1',
  clinicName: 'Clínica Ana',
  unit: '',
}

describe('validateLogin', () => {
  it('requires a valid email and a password', () => {
    expect(validateLogin({ email: 'ana', password: '' })).toEqual({
      email: 'Informe um e-mail válido.',
      password: 'Informe sua senha.',
    })
  })

  it('does not apply the password policy to existing accounts', () => {
    expect(validateLogin({ email: 'ana@clinica.com', password: '123' })).toEqual({})
  })
})

describe('validateNewPassword', () => {
  it.each(['curta1', 'somenteletras', '1234567890'])('rejects the weak password %s', (password) => {
    expect(validateNewPassword({ password, passwordConfirmation: password }).password).toBeDefined()
  })

  it('accepts accented letters with numbers', () => {
    expect(validateNewPassword({ password: 'coração123', passwordConfirmation: 'coração123' })).toEqual({})
  })

  it('requires the confirmation to match', () => {
    expect(validateNewPassword({ password: 'senhaSegura1', passwordConfirmation: 'outra' }).passwordConfirmation)
      .toBe('As senhas não coincidem.')
  })
})

describe('validateRegister', () => {
  it('accepts a complete form without a unit', () => {
    expect(validateRegister(validRegister)).toEqual({})
  })

  it('reports every invalid field', () => {
    const errors = validateRegister({ ...validRegister, name: ' ', email: 'x', password: 'a', clinicName: '' })

    expect(Object.keys(errors).sort()).toEqual(['clinicName', 'email', 'name', 'password', 'passwordConfirmation'])
  })
})

describe('validateForgotPassword', () => {
  it('requires a valid email', () => {
    expect(validateForgotPassword({ email: 'ana@' }).email).toBeDefined()
    expect(validateForgotPassword({ email: ' ana@clinica.com ' })).toEqual({})
  })
})

describe('parseApiError', () => {
  it('reads the first message of each field in the Laravel format', () => {
    const parsed = parseApiError({ data: { message: 'Dados inválidos.', errors: { email: ['Em uso.', 'Outro.'] } } })

    expect(parsed).toEqual({ message: 'Dados inválidos.', fields: { email: 'Em uso.' } })
  })

  it.each([[new Error('offline')], [null], [{ data: { message: 42 } }]])('falls back to a generic message for %o', (error) => {
    const parsed = parseApiError(error)

    expect(parsed.message).toContain('Tente novamente')
    expect(parsed.fields).toEqual({})
  })
})

describe('safeRedirect', () => {
  it('accepts panel paths', () => {
    expect(safeRedirect('/app/sorriso-perfeito/agenda')).toBe('/app/sorriso-perfeito/agenda')
  })

  it.each([
    '//site.com',
    'https://site.com',
    '/\\site.com',
    '/login',
    '/app//site.com',
    '/app/../login',
    '/app/x\\y',
    '',
    undefined,
    ['/app/x'],
  ])('rejects %o', (path) => {
    expect(safeRedirect(path)).toBeNull()
  })
})

describe('tenantPath', () => {
  it('builds the panel paths', () => {
    expect(tenantPath('clinica')).toBe('/app/clinica')
    expect(tenantPath('clinica', 'agenda')).toBe('/app/clinica/agenda')
  })
})
