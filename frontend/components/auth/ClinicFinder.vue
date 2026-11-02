<script setup lang="ts">
const { centralDomain } = useRuntimeConfig().public
const { clinicUrl } = useClinicHost()

const form = reactive({ slug: '' })

const { errors, isSubmitting, submit } = useAuthForm(form, validateClinicSlug, async () => {
  await navigateTo(clinicUrl(form.slug.trim().toLowerCase(), '/login'), { external: true })
})
</script>

<template>
  <div>
    <AuthHeading title="Encontre sua clínica" description="Informe o endereço da clínica para entrar." />

    <form class="grid gap-[18px]" novalidate @submit.prevent="submit">
      <AppFormField v-slot="field" label="Endereço da clínica" :error="errors.slug" size="large">
        <div class="flex items-center gap-2">
          <input
            :id="field.id"
            v-model="form.slug"
            autocomplete="off"
            autocapitalize="none"
            placeholder="sua-clinica"
            :class="[field.controlClass, 'placeholder:text-faint']"
            :aria-invalid="field.invalid"
            :aria-describedby="field.describedBy"
          >
          <span class="flex-none text-[13px] text-muted">.{{ centralDomain }}</span>
        </div>
      </AppFormField>

      <AuthSubmitButton label="Continuar" loading-label="Abrindo..." :loading="isSubmitting" />
    </form>

    <p class="mt-6 text-center text-[13px] text-muted">
      Ainda não tem conta?
      <NuxtLink to="/cadastro" class="font-semibold text-brand-text">
        Cadastre sua clínica
      </NuxtLink>
    </p>
  </div>
</template>
