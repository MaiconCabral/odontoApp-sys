<script setup lang="ts">
import type { NewPasswordForm } from '~/utils/auth'

definePageMeta({ layout: 'auth', middleware: ['clinic-host', 'guest'] })
useHead({ title: 'Redefinir senha' })

const route = useRoute()
const { resetPassword } = useAuth()

const token = typeof route.query.token === 'string' ? route.query.token : ''
const email = typeof route.query.email === 'string' ? route.query.email : ''

const form = reactive<NewPasswordForm>({ password: '', passwordConfirmation: '' })
const isLinkInvalid = ref(!token || !email)

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validateNewPassword, async () => {
  try {
    await resetPassword({ token, email, ...form })
  }
  catch (error) {
    if (!parseApiError(error).fields.token) {
      throw error
    }

    isLinkInvalid.value = true
  }
})
</script>

<template>
  <div>
    <template v-if="isLinkInvalid">
      <AuthHeading title="Link inválido ou expirado" description="Por segurança, os links de redefinição têm validade limitada." />

      <NuxtLink to="/recuperar-senha" class="text-[13px] font-semibold text-brand-text">
        Pedir um novo link
      </NuxtLink>
    </template>

    <template v-else>
      <AuthHeading title="Crie uma nova senha" :description="`Para a conta ${email}.`" />

      <AuthAlert v-if="formError">
        {{ formError }}
      </AuthAlert>

      <form class="grid gap-[18px]" novalidate @submit.prevent="submit">
        <AuthPasswordField
          v-model="form.password"
          label="Nova senha"
          autocomplete="new-password"
          :error="errors.password"
        />

        <AuthPasswordField
          v-model="form.passwordConfirmation"
          label="Confirme a nova senha"
          autocomplete="new-password"
          :error="errors.passwordConfirmation"
        />

        <AuthSubmitButton label="Salvar nova senha" loading-label="Salvando..." :loading="isSubmitting" />
      </form>
    </template>
  </div>
</template>
