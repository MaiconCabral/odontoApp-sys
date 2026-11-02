<script setup lang="ts">
import type { LoginPayload } from '~/types/auth'

definePageMeta({ layout: 'auth', middleware: 'guest' })
useHead({ title: 'Entrar' })

const route = useRoute()
const auth = useAuthStore()
const { login } = useAuth()
const { isClinicHost, centralUrl } = useClinicHost()

const form = reactive<LoginPayload>({
  email: typeof route.query.email === 'string' ? route.query.email : '',
  password: '',
})

const { errors, formError, isSubmitting, submit } = useAuthForm(
  form,
  validateLogin,
  () => login(form, route.query.redirect),
)
</script>

<template>
  <AuthClinicFinder v-if="!isClinicHost" />

  <div v-else-if="auth.clinicNotFound">
    <AuthHeading title="Clínica não encontrada" description="Confira o endereço digitado ou procure sua clínica novamente." />

    <a :href="centralUrl('/login')" class="text-[13px] font-semibold text-brand-text">
      Procurar minha clínica
    </a>
  </div>

  <div v-else>
    <AuthHeading title="Entrar na sua clínica" description="Use seu e-mail e senha para acessar o painel." />

    <AuthAlert v-if="route.query.cadastro" tone="success">
      Clínica criada. Entre com a senha que você acabou de definir.
    </AuthAlert>
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
      É paciente?
      <NuxtLink to="/agendar" class="font-semibold text-brand-text">
        Agende sua consulta
      </NuxtLink>
    </p>

    <p class="mt-6 text-center text-[13px] text-muted">
      Ainda não tem conta?
      <a :href="centralUrl('/cadastro')" class="font-semibold text-brand-text">
        Cadastre sua clínica
      </a>
    </p>
  </div>
</template>
