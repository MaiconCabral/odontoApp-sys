<script setup lang="ts">
import type { RegisterPayload } from '~/types/auth'

definePageMeta({ layout: 'auth', middleware: ['central-host', 'guest'] })
useHead({ title: 'Cadastro' })

const { register } = useAuth()

const form = reactive<RegisterPayload>({
  name: '',
  email: '',
  password: '',
  passwordConfirmation: '',
  clinicName: '',
  unit: '',
})

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validateRegister, () => register(form))

const legendClass = 'mb-3.5 font-display text-[14px] font-bold text-heading'
</script>

<template>
  <div>
    <AuthHeading title="Cadastre sua clínica" description="Crie a conta de administrador e comece a organizar a agenda." />

    <AuthAlert v-if="formError">
      {{ formError }}
    </AuthAlert>

    <form class="grid gap-7" novalidate @submit.prevent="submit">
      <fieldset class="grid gap-[18px]">
        <legend :class="legendClass">
          Você
        </legend>

        <AppFormField v-slot="field" label="Nome completo" :error="errors.name" size="large">
          <input
            :id="field.id"
            v-model="form.name"
            autocomplete="name"
            :class="field.controlClass"
            :aria-invalid="field.invalid"
            :aria-describedby="field.describedBy"
          >
        </AppFormField>

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
          autocomplete="new-password"
          :error="errors.password"
        />

        <AuthPasswordField
          v-model="form.passwordConfirmation"
          label="Confirme a senha"
          autocomplete="new-password"
          :error="errors.passwordConfirmation"
        />
      </fieldset>

      <fieldset class="grid gap-[18px]">
        <legend :class="legendClass">
          Sua clínica
        </legend>

        <AppFormField v-slot="field" label="Nome da clínica" :error="errors.clinicName" size="large">
          <input
            :id="field.id"
            v-model="form.clinicName"
            autocomplete="organization"
            :class="field.controlClass"
            :aria-invalid="field.invalid"
            :aria-describedby="field.describedBy"
          >
        </AppFormField>

        <AppFormField v-slot="field" label="Unidade (opcional)" :error="errors.unit" size="large">
          <input
            :id="field.id"
            v-model="form.unit"
            autocomplete="off"
            placeholder="Ex.: Unidade Jardins"
            :class="[field.controlClass, 'placeholder:text-faint']"
            :aria-invalid="field.invalid"
            :aria-describedby="field.describedBy"
          >
        </AppFormField>
      </fieldset>

      <AuthSubmitButton label="Criar conta" loading-label="Criando conta..." :loading="isSubmitting" />
    </form>

    <p class="mt-6 text-center text-[13px] text-muted">
      Já tem conta?
      <NuxtLink to="/login" class="font-semibold text-brand-text">
        Entrar
      </NuxtLink>
    </p>
  </div>
</template>
