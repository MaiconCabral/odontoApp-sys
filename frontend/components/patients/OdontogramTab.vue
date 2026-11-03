<script setup lang="ts">
import type { Odontogram, ToothCondition, ToothRecordForm, ToothSurface } from '~/types/clinical'

const props = defineProps<{
  patientId: string
}>()

const { showToast } = useToast()

const odontogram = ref<Odontogram>({ current: [], history: [] })
const loadFailed = ref(false)
const deciduous = ref(false)

// Current state and history of the teeth.
async function load() {
  loadFailed.value = false

  try {
    odontogram.value = await clinicalService.odontogram(props.patientId)
  }
  catch {
    loadFailed.value = true
  }
}

await load()

const form = reactive<ToothRecordForm>({ tooth: 0, surface: null, condition: 'caries', notes: '' })
const isWhole = computed(() => WHOLE_TOOTH.includes(form.condition))

// Picks a tooth, and the surface when one was clicked.
function select(tooth: number, surface: ToothSurface | null) {
  form.tooth = tooth
  form.surface = surface ?? form.surface ?? 'O'
}

// Conditions of the whole tooth carry no surface.
watch(() => form.condition, (condition: ToothCondition) => {
  if (WHOLE_TOOTH.includes(condition)) {
    form.surface = null
  }
  else if (!form.surface) {
    form.surface = 'O'
  }
})

// Checks the tooth and the surface before sending.
function validateTooth(fields: ToothRecordForm) {
  if (!fields.tooth) {
    return { tooth: 'Escolha um dente no odontograma.' }
  }

  return !WHOLE_TOOTH.includes(fields.condition) && !fields.surface ? { surface: 'Escolha a face do dente.' } : {}
}

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validateTooth, async () => {
  await clinicalService.recordTooth(props.patientId, { ...form })
  showToast(`Dente ${form.tooth} atualizado.`)
  form.notes = ''
  await load()
})

const conditions = Object.entries(CONDITION_LABELS) as [ToothCondition, string][]
const surfaces = Object.entries(SURFACE_LABELS) as [ToothSurface, string][]

// Swatch of the legend for a condition.
function legendClass(condition: ToothCondition) {
  return SURFACE_FILL[condition]?.swatch ?? ''
}
</script>

<template>
  <div class="grid gap-[17px]">
    <SettingsCard title="Odontograma" subtitle="Clique numa face do dente para registrar o que encontrou.">
      <template #action>
        <label class="flex items-center gap-2 text-[11px] text-body">
          <input v-model="deciduous" type="checkbox" class="accent-brand">
          Dentes decíduos
        </label>
      </template>

      <p v-if="loadFailed" role="alert" class="pt-4 text-[11px] text-muted">
        Não foi possível carregar o odontograma.
        <button type="button" class="font-bold text-brand-text" @click="load">
          Tentar novamente
        </button>
      </p>

      <template v-else>
        <PatientsOdontogram :records="odontogram.current" :deciduous="deciduous" :selected="form.tooth || null" @select="select" />

        <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-muted" aria-label="Legenda">
          <li v-for="[condition, label] in conditions" :key="condition" class="flex items-center gap-1">
            <span v-if="legendClass(condition)" class="h-2.5 w-2.5 rounded-sm border border-line" :class="legendClass(condition)" />
            <span v-else class="w-2.5 text-center font-bold" :class="TOOTH_MARK[condition]?.class">{{ TOOTH_MARK[condition]?.letter }}</span>
            {{ label }}
          </li>
        </ul>

        <form class="mt-4 grid gap-3 border-t border-line-soft pt-4 sm:grid-cols-[90px_1fr_1fr]" novalidate @submit.prevent="submit()">
          <p v-if="formError" role="alert" class="text-[11px] text-tone-red-text sm:col-span-3">
            {{ formError }}
          </p>

          <AppFormField v-slot="field" label="Dente" :error="errors.tooth">
            <input :id="field.id" :value="form.tooth || ''" readonly placeholder="—" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
          </AppFormField>

          <AppFormField v-slot="field" label="Situação">
            <select :id="field.id" v-model="form.condition" :class="field.controlClass">
              <option v-for="[condition, label] in conditions" :key="condition" :value="condition">
                {{ label }}
              </option>
            </select>
          </AppFormField>

          <AppFormField v-slot="field" label="Face" :error="errors.surface">
            <select :id="field.id" v-model="form.surface" :disabled="isWhole" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
              <option v-if="isWhole" :value="null">
                Dente inteiro
              </option>
              <option v-for="[surface, label] in surfaces" :key="surface" :value="surface">
                {{ label }}
              </option>
            </select>
          </AppFormField>

          <AppFormField v-slot="field" label="Observação (opcional)" full>
            <input :id="field.id" v-model="form.notes" maxlength="500" :class="field.controlClass">
          </AppFormField>

          <button
            type="submit"
            :disabled="isSubmitting"
            class="justify-self-start rounded-lg bg-brand px-[15px] py-2.5 text-[12px] font-bold text-white hover:bg-brand-hover disabled:opacity-60 sm:col-span-3"
          >
            Registrar
          </button>
        </form>
      </template>
    </SettingsCard>

    <SettingsCard v-if="odontogram.history.length" title="Histórico" subtitle="Últimas mudanças, da mais recente para a mais antiga.">
      <ol class="mt-[5px]">
        <li v-for="record in odontogram.history" :key="record.id" class="flex flex-wrap justify-between gap-2 border-b border-line-soft py-2 text-[11px] text-body last:border-b-0">
          <span>
            <strong>Dente {{ record.tooth }}</strong>
            <template v-if="record.surface">
              · {{ SURFACE_LABELS[record.surface] }}
            </template>
            · {{ record.conditionLabel }}
            <span v-if="record.notes" class="text-muted"> — {{ record.notes }}</span>
          </span>
          <span class="text-[10px] text-muted">{{ record.author }} · {{ new Date(record.createdAt).toLocaleDateString('pt-BR') }}</span>
        </li>
      </ol>
    </SettingsCard>
  </div>
</template>
