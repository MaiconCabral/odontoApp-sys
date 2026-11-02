import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import FormField from '~/components/app/FormField.vue'
import Sidebar from '~/components/layout/Sidebar.vue'
import RegisterPage from '~/pages/cadastro.vue'
import LoginPage from '~/pages/login.vue'
import ForgotPasswordPage from '~/pages/recuperar-senha.vue'
import ResetPasswordPage from '~/pages/redefinir-senha.vue'
import { clearSession } from '../utils/session'

const { authMock, hostMock } = vi.hoisted(() => ({
  hostMock: {
    isClinicHost: true,
    centralUrl: (path: string) => `http://localhost:3000${path}`,
    clinicUrl: (slug: string, path: string) => `http://${slug}.localhost:3000${path}`,
  },
  authMock: {
    loadSession: vi.fn(),
    login: vi.fn(),
    register: vi.fn(),
    forgotPassword: vi.fn(),
    resetPassword: vi.fn(),
    logout: vi.fn(),
  },
}))

mockNuxtImport('useAuth', () => () => authMock)
mockNuxtImport('useClinicHost', () => () => hostMock)

function validationError(message: string, errors: Record<string, string[]> = {}) {
  return Object.assign(new Error(message), { data: { message, errors } })
}

function fieldByLabel(root: VueWrapper, label: string) {
  const element = root.findAll('label').find(item => item.text() === label)

  return root.find(`[id="${element?.attributes('for')}"]`)
}

async function fill(root: VueWrapper, values: Record<string, string>) {
  for (const [label, value] of Object.entries(values)) {
    await fieldByLabel(root, label).setValue(value)
  }
}

async function submit(root: VueWrapper) {
  await root.find('form').trigger('submit')
  await flushPromises()
}

describe('auth pages', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    clearSession()
    hostMock.isClinicHost = true
  })

  describe('login', () => {
    it('blocks an invalid form and marks the fields', async () => {
      const page = await mountSuspended(LoginPage, { route: '/login' })

      await submit(page)

      expect(page.text()).toContain('Informe um e-mail válido.')
      expect(page.text()).toContain('Informe sua senha.')
      expect(fieldByLabel(page, 'E-mail').attributes('aria-invalid')).toBe('true')
      expect(authMock.login).not.toHaveBeenCalled()
    })

    it('sends the credentials and the requested destination', async () => {
      authMock.login.mockResolvedValue(undefined)

      const page = await mountSuspended(LoginPage, { route: '/login?redirect=/app/agenda' })

      await fill(page, { 'E-mail': 'demo@sorriso.app', 'Senha': 'Sorriso123' })
      await submit(page)

      expect(authMock.login).toHaveBeenCalledWith(
        expect.objectContaining({ email: 'demo@sorriso.app', password: 'Sorriso123' }),
        '/app/agenda',
      )
    })

    it('shows the API message when the credentials are wrong', async () => {
      authMock.login.mockRejectedValue(validationError('E-mail ou senha incorretos.'))

      const page = await mountSuspended(LoginPage, { route: '/login' })

      await fill(page, { 'E-mail': 'demo@sorriso.app', 'Senha': 'errada' })
      await submit(page)

      expect(page.find('[role="alert"]').text()).toBe('E-mail ou senha incorretos.')
    })

    it('shows a generic message when the API is unreachable', async () => {
      authMock.login.mockRejectedValue(new Error('network down'))

      const page = await mountSuspended(LoginPage, { route: '/login' })

      await fill(page, { 'E-mail': 'demo@sorriso.app', 'Senha': 'x' })
      await submit(page)

      expect(page.find('[role="alert"]').text()).toContain('Tente novamente')
      expect(page.text()).not.toContain('network down')
    })

    it('shows the loading state and ignores a second submit', async () => {
      let finish = () => {}
      authMock.login.mockReturnValue(new Promise<void>((resolve) => {
        finish = resolve
      }))

      const page = await mountSuspended(LoginPage, { route: '/login' })

      await fill(page, { 'E-mail': 'demo@sorriso.app', 'Senha': 'Sorriso123' })
      await page.find('form').trigger('submit')
      await page.find('form').trigger('submit')

      const button = page.find('button[type="submit"]')

      expect(authMock.login).toHaveBeenCalledTimes(1)
      expect(button.text()).toBe('Entrando...')
      expect(button.attributes('disabled')).toBeDefined()

      finish()
      await flushPromises()

      expect(page.find('button[type="submit"]').text()).toBe('Entrar')
    })

    it('toggles the password visibility', async () => {
      const page = await mountSuspended(LoginPage, { route: '/login' })

      expect(fieldByLabel(page, 'Senha').attributes('type')).toBe('password')

      await page.find('[aria-label="Mostrar senha"]').trigger('click')

      expect(fieldByLabel(page, 'Senha').attributes('type')).toBe('text')
      expect(page.find('[aria-label="Ocultar senha"]').attributes('aria-pressed')).toBe('true')
    })

    it('asks for the clinic on the central domain', async () => {
      hostMock.isClinicHost = false

      const page = await mountSuspended(LoginPage, { route: '/login' })

      expect(page.text()).toContain('Encontre sua clínica')
      expect(page.find('input[type="password"]').exists()).toBe(false)
    })

    it('refuses an invalid clinic address', async () => {
      hostMock.isClinicHost = false

      const page = await mountSuspended(LoginPage, { route: '/login' })

      await fill(page, { 'Endereço da clínica': 'evil.com/x' })
      await submit(page)

      expect(page.text()).toContain('Informe o endereço da clínica.')
    })

    it('explains a clinic that does not exist', async () => {
      useAuthStore().clinicNotFound = true

      const page = await mountSuspended(LoginPage, { route: '/login' })

      expect(page.text()).toContain('Clínica não encontrada')
      expect(page.find('a').attributes('href')).toBe('http://localhost:3000/login')
    })

    it('welcomes a new clinic with the e-mail filled in', async () => {
      const page = await mountSuspended(LoginPage, { route: '/login?cadastro=1&email=ana@clinica.com' })

      expect(page.find('[role="status"]').text()).toContain('Clínica criada')
      expect((fieldByLabel(page, 'E-mail').element as HTMLInputElement).value).toBe('ana@clinica.com')
    })

    it('confirms a password that was just reset', async () => {
      const page = await mountSuspended(LoginPage, { route: '/login?redefinida=1' })

      expect(page.find('[role="status"]').text()).toContain('Senha redefinida')
    })
  })

  describe('register', () => {
    const valid = {
      'Nome completo': 'Ana Souza',
      'E-mail': 'ana@clinica.com',
      'Senha': 'senhaSegura1',
      'Confirme a senha': 'senhaSegura1',
      'Nome da clínica': 'Clínica Ana',
    }

    it('validates the password and the clinic before sending', async () => {
      const page = await mountSuspended(RegisterPage, { route: '/cadastro' })

      await fill(page, { ...valid, 'Senha': 'fraca', 'Nome da clínica': '' })
      await submit(page)

      expect(page.text()).toContain('Use ao menos 8 caracteres, com letras e números.')
      expect(page.text()).toContain('As senhas não coincidem.')
      expect(page.text()).toContain('Informe o nome da clínica.')
      expect(authMock.register).not.toHaveBeenCalled()
    })

    it('registers the clinic with a valid form', async () => {
      authMock.register.mockResolvedValue(undefined)

      const page = await mountSuspended(RegisterPage, { route: '/cadastro' })

      await fill(page, valid)
      await submit(page)

      expect(authMock.register).toHaveBeenCalledWith(expect.objectContaining({ clinicName: 'Clínica Ana', unit: '' }))
    })

    it('shows a duplicated email on its field', async () => {
      authMock.register.mockRejectedValue(validationError('Dados inválidos.', { email: ['Este e-mail já está em uso.'] }))

      const page = await mountSuspended(RegisterPage, { route: '/cadastro' })

      await fill(page, valid)
      await submit(page)

      expect(fieldByLabel(page, 'E-mail').attributes('aria-invalid')).toBe('true')
      expect(page.text()).toContain('Este e-mail já está em uso.')
      expect(page.find('[role="alert"]').exists()).toBe(false)
    })
  })

  describe('forgot password', () => {
    it('shows a neutral confirmation after sending', async () => {
      authMock.forgotPassword.mockResolvedValue(undefined)

      const page = await mountSuspended(ForgotPasswordPage, { route: '/recuperar-senha' })

      await fill(page, { 'E-mail': 'alguem@clinica.com' })
      await submit(page)

      expect(page.find('[role="status"]').text()).toContain('Se houver uma conta para alguem@clinica.com')
      expect(page.find('form').exists()).toBe(false)

      await page.findAll('button').find(button => button.text() === 'Usar outro e-mail')?.trigger('click')

      expect(page.find('form').exists()).toBe(true)
    })

    it('stays on the form when the request fails', async () => {
      authMock.forgotPassword.mockRejectedValue(new Error('offline'))

      const page = await mountSuspended(ForgotPasswordPage, { route: '/recuperar-senha' })

      await fill(page, { 'E-mail': 'alguem@clinica.com' })
      await submit(page)

      expect(page.find('[role="alert"]').exists()).toBe(true)
      expect(page.find('form').exists()).toBe(true)
    })
  })

  describe('reset password', () => {
    const route = '/redefinir-senha?token=abc&email=ana@clinica.com'

    it('explains a link without a token', async () => {
      const page = await mountSuspended(ResetPasswordPage, { route: '/redefinir-senha' })

      expect(page.text()).toContain('Link inválido ou expirado')
      expect(page.find('form').exists()).toBe(false)
    })

    it('sends the token, the email and the new password', async () => {
      authMock.resetPassword.mockResolvedValue(undefined)

      const page = await mountSuspended(ResetPasswordPage, { route })

      await fill(page, { 'Nova senha': 'novaSenha123', 'Confirme a nova senha': 'novaSenha123' })
      await submit(page)

      expect(authMock.resetPassword).toHaveBeenCalledWith({
        token: 'abc',
        email: 'ana@clinica.com',
        password: 'novaSenha123',
        passwordConfirmation: 'novaSenha123',
      })
    })

    it('switches to the invalid link state when the token expired', async () => {
      authMock.resetPassword.mockRejectedValue(validationError('Expirado.', { token: ['Expirado.'] }))

      const page = await mountSuspended(ResetPasswordPage, { route })

      await fill(page, { 'Nova senha': 'novaSenha123', 'Confirme a nova senha': 'novaSenha123' })
      await submit(page)

      expect(page.text()).toContain('Pedir um novo link')
      expect(page.find('form').exists()).toBe(false)
    })
  })

  it('signs out from the sidebar', async () => {
    const sidebar = await mountSuspended(Sidebar, { props: { collapsed: false, mobileOpen: false } })

    await sidebar.findAll('button').find(button => button.text() === 'Sair da conta')?.trigger('click')

    expect(authMock.logout).toHaveBeenCalledTimes(1)
  })
})

describe('FormField', () => {
  it('keeps the dense size by default and offers the large one', async () => {
    const dense = await mountSuspended(FormField, {
      props: { label: 'Campo' },
      slots: { default: (field: { controlClass: string }) => field.controlClass },
    })
    const large = await mountSuspended(FormField, {
      props: { label: 'Campo', size: 'large' },
      slots: { default: (field: { controlClass: string }) => field.controlClass },
    })

    expect(dense.text()).toContain('h-[37px]')
    expect(large.text()).toContain('h-11')
    expect(large.find('label').classes()).toContain('text-[13px]')
  })
})
