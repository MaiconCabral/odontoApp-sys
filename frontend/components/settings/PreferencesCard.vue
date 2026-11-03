<script setup lang="ts">
import type { ClinicPreferences, ServicePreference } from '~/types/settings'

const props = defineProps<{
  preferences: ServicePreference[]
}>()

const { showToast } = useToast()
const items = ref(props.preferences.map(preference => ({ ...preference })))
const saving = ref(false)

// The three toggles as the API expects them; the API always receives all of them.
function currentValues(): ClinicPreferences {
  return Object.fromEntries(items.value.map(item => [item.id, item.enabled])) as ClinicPreferences
}

// Flips the toggle right away and saves; puts it back when the API refuses.
async function toggle(item: ServicePreference) {
  item.enabled = !item.enabled
  saving.value = true

  try {
    await settingsService.updatePreferences(currentValues())
    showToast('Preferências salvas.')
  }
  catch {
    item.enabled = !item.enabled
    showToast('Não foi possível salvar a preferência. Tente novamente.')
  }
  finally {
    saving.value = false
  }
}
</script>

<template>
  <SettingsCard
    title="Preferências de atendimento"
    subtitle="Defina como sua equipe trabalha no dia a dia."
  >
    <div
      v-for="item in items"
      :key="item.id"
      class="flex items-center justify-between gap-5 border-b border-line-soft py-4 last:border-b-0 last:pb-0"
    >
      <div class="flex flex-col gap-1">
        <strong :id="`preference-${item.id}`" class="text-[11px] text-body dark:text-body">{{ item.title }}</strong>
        <span class="text-[10px] text-muted">{{ item.description }}</span>
      </div>
      <button
        type="button"
        role="switch"
        class="h-5 w-[35px] flex-none rounded-[15px] p-0.5 disabled:cursor-wait disabled:opacity-70"
        :class="item.enabled ? 'bg-brand' : 'bg-line'"
        :aria-checked="item.enabled"
        :aria-labelledby="`preference-${item.id}`"
        :disabled="saving"
        @click="toggle(item)"
      >
        <i
          class="block h-4 w-4 rounded-full bg-white transition duration-200"
          :class="{ 'translate-x-[15px]': item.enabled }"
        />
      </button>
    </div>
  </SettingsCard>
</template>
