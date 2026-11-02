<script setup lang="ts">
import type { BookingForm } from '~/types/schedule'

definePageMeta({ layout: 'auth', middleware: 'clinic-host' })
useHead({ title: 'Agendar consulta' })

const { data: info, error, pending, refresh } = await useAsyncData('public-booking', () => publicBookingService.info())

const step = ref<1 | 2 | 3 | 4>(1)
const confirmation = ref('')

const form = reactive<BookingForm>({
  procedure: '',
  professionalId: '',
  date: '',
  time: '',
  name: '',
  phone: '',
  email: '',
  notes: '',
  consent: false,
  website: '',
})

const minDate = addDays(localToday(), 1)
const maxDate = computed(() => addDays(localToday(), info.value?.maxDaysAhead ?? 60))

const slots = ref<string[]>([])
const isLoadingSlots = ref(false)
const slotError = ref('')

// Loads the free times of the chosen day.
async function loadSlots() {
  if (!form.date || !form.procedure) {
    slots.value = []

    return
  }

  isLoadingSlots.value = true
  slotError.value = ''

  try {
    slots.value = await publicBookingService.availability(form)
  }
  catch (failure) {
    slots.value = []
    slotError.value = Object.values(parseApiError(failure).fields)[0] ?? 'Não foi possível buscar os horários.'
  }
  finally {
    isLoadingSlots.value = false
  }

  if (!slots.value.includes(form.time)) {
    form.time = ''
  }
}

watch(() => [form.date, form.procedure, form.professionalId], loadSlots)

const procedureLabel = computed(() => info.value?.procedures.find(procedure => procedure.key === form.procedure)?.label ?? '')
const professionalLabel = computed(() =>
  info.value?.professionals.find(professional => professional.id === form.professionalId)?.name ?? 'Sem preferência',
)

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validateBookingContact, async () => {
  try {
    confirmation.value = (await publicBookingService.request(form)).message
    step.value = 4
  }
  catch (failure) {
    const { fields } = parseApiError(failure)

    // The time was taken while the patient typed: back to the times, with fresh ones.
    if (fields.startsAt || fields.professionalId || fields.date || fields.time) {
      step.value = 2
      await loadSlots()
      slotError.value = fields.startsAt ?? fields.professionalId ?? fields.date ?? fields.time ?? ''

      return
    }

    throw failure
  }
})

const optionClass = 'flex items-center justify-between gap-3 rounded-lg border px-3 py-2.5 text-left text-[13px] transition duration-200'
const primaryClass = 'inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand text-[14px] font-bold text-white disabled:opacity-60'
</script>

<template>
  <div>
    <p v-if="pending && !info" role="status" class="text-[13px] text-muted">
      Carregando...
    </p>

    <div v-else-if="error || !info" role="alert" class="text-[13px] text-muted">
      <p>Não foi possível abrir o agendamento desta clínica.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <template v-else>
      <AuthHeading
        :title="step === 4 ? 'Pedido enviado' : `Agendar em ${info.clinic.name}`"
        :description="step === 4 ? confirmation : 'Escolha o atendimento e um horário livre. A clínica confirma o pedido.'"
      />

      <p v-if="step < 4" class="mb-5 text-[12px] font-semibold text-muted" aria-live="polite">
        Etapa {{ step }} de 3
      </p>

      <section v-if="step === 1" class="grid gap-[18px]" aria-label="Atendimento">
        <fieldset class="grid gap-2">
          <legend class="mb-2 text-[13px] font-semibold text-muted">
            Procedimento
          </legend>
          <label
            v-for="procedure in info.procedures"
            :key="procedure.key"
            :class="[optionClass, form.procedure === procedure.key ? 'border-brand-accent bg-brand-tint' : 'border-line']"
          >
            <span class="flex items-center gap-2">
              <input v-model="form.procedure" type="radio" name="procedure" :value="procedure.key" class="accent-brand">
              {{ procedure.label }}
            </span>
            <span class="text-[11px] text-muted">{{ procedure.duration }} min</span>
          </label>
        </fieldset>

        <AppFormField v-slot="field" label="Profissional" size="large">
          <select :id="field.id" v-model="form.professionalId" :class="field.controlClass">
            <option value="">
              Sem preferência
            </option>
            <option v-for="professional in info.professionals" :key="professional.id" :value="professional.id">
              {{ professional.name }}{{ professional.specialty ? ` · ${professional.specialty}` : '' }}
            </option>
          </select>
        </AppFormField>

        <button type="button" :class="primaryClass" :disabled="!form.procedure" @click="step = 2">
          Escolher horário
        </button>
      </section>

      <section v-else-if="step === 2" class="grid gap-[18px]" aria-label="Data e horário">
        <AppFormField v-slot="field" label="Data" size="large">
          <input :id="field.id" v-model="form.date" type="date" :min="minDate" :max="maxDate" :class="field.controlClass">
        </AppFormField>

        <AuthAlert v-if="slotError">
          {{ slotError }}
        </AuthAlert>

        <div v-if="form.date">
          <p v-if="isLoadingSlots" role="status" class="text-[13px] text-muted">
            Buscando horários livres...
          </p>
          <p v-else-if="!slots.length" class="text-[13px] text-muted">
            Nenhum horário livre neste dia. Escolha outra data.
          </p>
          <div v-else class="grid grid-cols-4 gap-2" role="group" aria-label="Horários livres">
            <button
              v-for="time in slots"
              :key="time"
              type="button"
              class="rounded-lg border py-2 text-[13px] font-semibold transition duration-200"
              :class="form.time === time ? 'border-brand-accent bg-brand text-white' : 'border-line text-body hover:border-brand-accent'"
              :aria-pressed="form.time === time"
              @click="form.time = time"
            >
              {{ time }}
            </button>
          </div>
        </div>

        <div class="flex gap-2">
          <button type="button" class="h-11 rounded-lg border border-line px-4 text-[13px] font-semibold text-body" @click="step = 1">
            Voltar
          </button>
          <button type="button" :class="primaryClass" :disabled="!form.time" @click="step = 3">
            Continuar
          </button>
        </div>
      </section>

      <section v-else-if="step === 3" aria-label="Seus dados">
        <p class="mb-4 rounded-lg bg-brand-tint px-3 py-2.5 text-[12px] text-brand-text">
          {{ procedureLabel }} · {{ formatAppointmentDate(form.date, form.time) }} · {{ professionalLabel }}
        </p>

        <AuthAlert v-if="formError">
          {{ formError }}
        </AuthAlert>

        <form class="grid gap-[18px]" novalidate @submit.prevent="submit">
          <AppFormField v-slot="field" label="Nome completo" :error="errors.name" size="large">
            <input :id="field.id" v-model="form.name" autocomplete="name" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
          </AppFormField>

          <AppFormField v-slot="field" label="Telefone com DDD" :error="errors.phone" size="large">
            <input :id="field.id" v-model="form.phone" type="tel" autocomplete="tel" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
          </AppFormField>

          <AppFormField v-slot="field" label="E-mail (opcional)" :error="errors.email" size="large">
            <input :id="field.id" v-model="form.email" type="email" autocomplete="email" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
          </AppFormField>

          <AppFormField v-slot="field" label="Observações (opcional)" size="large">
            <textarea :id="field.id" v-model="form.notes" rows="2" maxlength="500" :class="[field.controlClass, '!h-auto py-2']" />
          </AppFormField>

          <!-- Hidden from people. Bots fill it, and the API then saves nothing. -->
          <div class="absolute left-[-9999px]" aria-hidden="true">
            <label for="booking-website">Não preencha este campo</label>
            <input id="booking-website" v-model="form.website" tabindex="-1" autocomplete="off">
          </div>

          <label class="flex items-start gap-2 text-[12px] text-body">
            <input v-model="form.consent" type="checkbox" class="mt-0.5 accent-brand" :aria-invalid="Boolean(errors.consent)">
            Autorizo a clínica a entrar em contato comigo para confirmar o horário.
          </label>
          <p v-if="errors.consent" class="-mt-3 text-[12px] text-[#c2553f]">
            {{ errors.consent }}
          </p>

          <div class="flex gap-2">
            <button type="button" class="h-11 rounded-lg border border-line px-4 text-[13px] font-semibold text-body" @click="step = 2">
              Voltar
            </button>
            <AuthSubmitButton label="Enviar pedido" loading-label="Enviando..." :loading="isSubmitting" />
          </div>
        </form>
      </section>

      <section v-else aria-label="Pedido enviado">
        <AuthAlert tone="success">
          {{ procedureLabel }} · {{ formatAppointmentDate(form.date, form.time) }}
        </AuthAlert>
        <p class="text-[13px] text-muted">
          Guarde este horário. Se precisar mudar algo, fale com a clínica{{ info.clinic.phone ? ` pelo ${info.clinic.phone}` : '' }}.
        </p>
      </section>
    </template>
  </div>
</template>
