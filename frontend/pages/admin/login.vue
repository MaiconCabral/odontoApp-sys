<script setup lang="ts">
import type { LoginPayload } from '~/types/auth'

definePageMeta({ layout: 'auth', middleware: 'central-host' })
useHead({ title: 'Plataforma' })

const route = useRoute()
const { login } = usePlatformSession()

const form = reactive<LoginPayload>({ email: '', password: '' })

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validateLogin, async () => {
  await login(form.email, form.password)
  await navigateTo(safePanelRedirect(route.query.redirect))
})
</script>

<template>
  <div>
    <AuthHeading title="Painel da plataforma" description="Acesso da equipe que administra as clínicas e os planos." />

    <AuthAlert v-if="formError">
      {{ formError }}
    </AuthAlert>

    <form class="grid gap-[18px]" novalidate @submit.prevent="submit">
      <AppFormField v-slot="field" label="E-mail" :error="errors.email" size="large">
        <input :id="field.id" v-model="form.email" type="email" autocomplete="email" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
      </AppFormField>
      <AppFormField v-slot="field" label="Senha" :error="errors.password" size="large">
        <input :id="field.id" v-model="form.password" type="password" autocomplete="current-password" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
      </AppFormField>
      <AuthSubmitButton label="Entrar" loading-label="Entrando..." :loading="isSubmitting" />
    </form>
  </div>
</template>
