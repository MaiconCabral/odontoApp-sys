<script setup lang="ts">
import type { ForgotPasswordPayload } from '~/types/auth'

definePageMeta({ layout: 'auth', middleware: ['clinic-host', 'guest'] })
useHead({ title: 'Recuperar senha' })

const { forgotPassword } = useAuth()

const form = reactive<ForgotPasswordPayload>({ email: '' })
const isSent = ref(false)

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validateForgotPassword, async () => {
  await forgotPassword(form)
  isSent.value = true
})
</script>

<template>
  <div>
    <template v-if="isSent">
      <AuthHeading title="Verifique seu e-mail" description="Enviamos as instruções se os dados conferirem." />

      <AuthAlert tone="success">
        Se houver uma conta para {{ form.email.trim() }}, você receberá um link para criar uma nova senha.
      </AuthAlert>

      <button type="button" class="text-[13px] font-semibold text-brand-text" @click="isSent = false">
        Usar outro e-mail
      </button>
    </template>

    <template v-else>
      <AuthHeading title="Recuperar senha" description="Informe seu e-mail e enviaremos um link para criar uma nova senha." />

      <AuthAlert v-if="formError">
        {{ formError }}
      </AuthAlert>

      <form class="grid gap-[18px]" novalidate @submit.prevent="submit">
        <AppFormField v-slot="field" label="E-mail" :error="errors.email" size="large">
          <input
            :id="field.id"
            v-model="form.email"
            type="email"
            autocomplete="email"
            :class="field.controlClass"
            :aria-invalid="field.invalid"
            :aria-describedby="field.describedBy"
          >
        </AppFormField>

        <AuthSubmitButton label="Enviar link" loading-label="Enviando..." :loading="isSubmitting" />
      </form>
    </template>

    <p class="mt-6 text-center text-[13px] text-muted">
      Lembrou a senha?
      <NuxtLink to="/login" class="font-semibold text-brand-text">
        Voltar para entrar
      </NuxtLink>
    </p>
  </div>
</template>
