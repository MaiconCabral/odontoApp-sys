<script setup lang="ts">
import type { LoginPayload } from '~/types/auth'

definePageMeta({ layout: 'auth', middleware: 'guest' })
useHead({ title: 'Entrar' })

const route = useRoute()
const { login } = useAuth()

const form = reactive<LoginPayload>({ email: '', password: '' })

const { errors, formError, isSubmitting, submit } = useAuthForm(
  form,
  validateLogin,
  () => login(form, route.query.redirect),
)
</script>

<template>
  <div>
    <AuthHeading title="Entrar na sua clínica" description="Use seu e-mail e senha para acessar o painel." />

    <AuthAlert v-if="route.query.redefinida" tone="success">
      Senha redefinida. Entre com a nova senha.
    </AuthAlert>
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

      <AuthPasswordField
        v-model="form.password"
        label="Senha"
        autocomplete="current-password"
        :error="errors.password"
      />

      <NuxtLink to="/recuperar-senha" class="-mt-1.5 justify-self-end text-[13px] font-semibold text-brand-text">
        Esqueci minha senha
      </NuxtLink>

      <AuthSubmitButton label="Entrar" loading-label="Entrando..." :loading="isSubmitting" />
    </form>

    <p class="mt-6 text-center text-[13px] text-muted">
      Ainda não tem conta?
      <NuxtLink to="/cadastro" class="font-semibold text-brand-text">
        Cadastre sua clínica
      </NuxtLink>
    </p>
  </div>
</template>
