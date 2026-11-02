<script setup lang="ts">
import { UserPlus } from 'lucide-vue-next'
import type { Patient, PatientForm } from '~/types/patients'

const { closeModal, modalPayload } = useActiveModal()
const { showToast } = useToast()

const editing = (modalPayload.value?.patient ?? null) as Patient | null

const form = reactive<PatientForm>({
  name: editing?.name ?? '',
  cpf: editing?.cpf ?? '',
  phone: editing?.phone ?? '',
  email: editing?.email ?? '',
  insurance: editing?.insurance ?? INSURANCES[0] ?? '',
  birthDate: editing?.birthDate ?? '',
})

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validatePatient, async () => {
  const body = { ...form, cpf: formatCpf(form.cpf) || form.cpf, phone: formatPhone(form.phone) || form.phone }
  const saved = editing ? await patientsService.update(editing.id, body) : await patientsService.create(body)

  showToast(editing ? `Cadastro de ${saved.name} atualizado.` : `Cadastro de ${saved.name} concluído.`)
  await refreshNuxtData('patients')
  closeModal()
})
</script>

<template>
  <AppModal
    eyebrow="Relacionamento"
    :title="editing ? 'Editar paciente' : 'Novo paciente'"
    :description="editing ? 'Atualize os dados do paciente.' : 'Preencha os dados para cadastrar um paciente.'"
    :submit-label="editing ? 'Salvar alterações' : 'Cadastrar paciente'"
    :submitting="isSubmitting"
    @close="closeModal"
    @submit="submit()"
  >
    <template #submit-icon>
      <UserPlus :size="17" />
    </template>

    <p v-if="formError" role="alert" class="text-[11px] text-[#c2553f] sm:col-span-2">
      {{ formError }}
    </p>

    <AppFormField v-slot="field" label="Nome completo" :error="errors.name" full>
      <input
        :id="field.id"
        v-model="form.name"
        autofocus
        autocomplete="off"
        :class="field.controlClass"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
    </AppFormField>

    <AppFormField v-slot="field" label="CPF (opcional)" :error="errors.cpf">
      <input
        :id="field.id"
        v-model="form.cpf"
        inputmode="numeric"
        autocomplete="off"
        placeholder="000.000.000-00"
        :class="[field.controlClass, 'placeholder:text-faint']"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
    </AppFormField>

    <AppFormField v-slot="field" label="Telefone" :error="errors.phone">
      <input
        :id="field.id"
        v-model="form.phone"
        type="tel"
        autocomplete="off"
        placeholder="(11) 90000-0000"
        :class="[field.controlClass, 'placeholder:text-faint']"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
    </AppFormField>

    <AppFormField v-slot="field" label="E-mail (opcional)" :error="errors.email">
      <input
        :id="field.id"
        v-model="form.email"
        type="email"
        autocomplete="off"
        :class="field.controlClass"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
    </AppFormField>

    <AppFormField v-slot="field" label="Nascimento (opcional)" :error="errors.birthDate">
      <input :id="field.id" v-model="form.birthDate" type="date" :class="field.controlClass" :aria-invalid="field.invalid">
    </AppFormField>

    <AppFormField v-slot="field" label="Convênio" full>
      <select :id="field.id" v-model="form.insurance" :class="field.controlClass">
        <option v-for="insurance in INSURANCES" :key="insurance" :value="insurance">
          {{ insurance }}
        </option>
      </select>
    </AppFormField>
  </AppModal>
</template>
