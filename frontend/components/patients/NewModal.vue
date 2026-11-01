<script setup lang="ts">
import { UserPlus } from 'lucide-vue-next'
import type { NewPatientErrors, NewPatientForm } from '~/utils/patients'

const { closeModal } = useActiveModal()
const { showToast } = useToast()
const sessionPatients = useSessionPatients()

const { data: list } = await usePatients()

const form = reactive<NewPatientForm>({
  name: '',
  cpf: '',
  phone: '',
  insurance: list.value?.insurances[0] ?? '',
})

const errors = ref<NewPatientErrors>({})

function submit() {
  const patients = [...sessionPatients.value, ...(list.value?.patients ?? [])]

  errors.value = validateNewPatient(form, patients)

  if (Object.keys(errors.value).length > 0) {
    return
  }

  const name = form.name.trim()

  sessionPatients.value = [{
    id: `session-patient-${sessionPatients.value.length + 1}`,
    initials: patientInitials(name),
    name,
    tone: 'blue',
    cpf: formatCpf(form.cpf),
    phone: formatPhone(form.phone),
    lastAppointment: 'Sem consultas',
    insurance: form.insurance,
    isPrivate: form.insurance === 'Particular',
  }, ...sessionPatients.value]

  showToast(`Cadastro de ${name} concluído.`)
  closeModal()
}
</script>

<template>
  <AppModal
    v-if="list"
    eyebrow="Relacionamento"
    title="Novo paciente"
    description="Preencha os dados para cadastrar um paciente."
    submit-label="Cadastrar paciente"
    @close="closeModal"
    @submit="submit"
  >
    <template #submit-icon>
      <UserPlus :size="17" />
    </template>

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

    <AppFormField v-slot="field" label="CPF" :error="errors.cpf">
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

    <AppFormField v-slot="field" label="Convênio" full>
      <select :id="field.id" v-model="form.insurance" :class="field.controlClass">
        <option v-for="insurance in list.insurances" :key="insurance" :value="insurance">
          {{ insurance }}
        </option>
      </select>
    </AppFormField>
  </AppModal>
</template>
