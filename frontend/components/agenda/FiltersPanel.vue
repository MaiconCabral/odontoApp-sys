<script setup lang="ts">
import { Clock3, Plus } from 'lucide-vue-next'
import type { AgendaProfessional, AgendaRoom, ProfessionalTone } from '~/types/agenda'

const props = defineProps<{
  professionals: AgendaProfessional[]
  rooms: AgendaRoom[]
  freeSlotsToday: number
}>()

const selectedProfessionals = defineModel<string[]>('selectedProfessionals', { required: true })
const selectedRooms = defineModel<string[]>('selectedRooms', { required: true })

const toneClasses: Record<ProfessionalTone, string> = {
  cyan: 'bg-[#55bdd0]',
  amber: 'bg-[#e4ad4c]',
  green: 'bg-[#51bd92]',
}

const allProfessionalsSelected = computed(() => selectedProfessionals.value.length === props.professionals.length)
const someProfessionalsSelected = computed(() => selectedProfessionals.value.length > 0 && !allProfessionalsSelected.value)

function toggleAllProfessionals() {
  selectedProfessionals.value = allProfessionalsSelected.value
    ? []
    : props.professionals.map(professional => professional.id)
}

const headingClass = 'col-span-2 mb-3 flex items-center justify-between text-[11px] text-[#526176] dark:text-body md:col-span-1'
const addButtonClass = 'text-[#0a90ad]'
const rowClass = 'my-[13px] flex items-center gap-2 text-[10px] text-[#748296] dark:text-muted'
const checkboxClass = 'h-[13px] w-[13px] accent-[#0c93b2]'
</script>

<template>
  <aside class="grid grid-cols-2 gap-x-3 gap-y-1 rounded-xl border border-[#e7edf3] bg-surface p-4 shadow-[0_4px_14px_rgba(42,64,91,.025)] dark:border-line sm:px-[15px] sm:py-[17px] md:block">
    <div :class="headingClass">
      <strong>Profissionais</strong>
      <button type="button" :class="addButtonClass" aria-label="Adicionar profissional">
        <Plus :size="15" />
      </button>
    </div>
    <label :class="rowClass">
      <input
        type="checkbox"
        :class="checkboxClass"
        :checked="allProfessionalsSelected"
        :indeterminate="someProfessionalsSelected"
        @change="toggleAllProfessionals"
      >
      <span class="h-[7px] w-[7px] flex-none rounded-sm bg-[#0c91b0]" />
      Todos os profissionais
    </label>
    <label v-for="professional in professionals" :key="professional.id" :class="rowClass">
      <input v-model="selectedProfessionals" type="checkbox" :class="checkboxClass" :value="professional.id">
      <span class="h-[7px] w-[7px] flex-none rounded-sm" :class="toneClasses[professional.tone]" />
      {{ professional.name }}
    </label>

    <div class="my-[23px] hidden h-px bg-line-soft md:block" />

    <div :class="headingClass">
      <strong>Salas</strong>
      <button type="button" :class="addButtonClass" aria-label="Adicionar sala">
        <Plus :size="15" />
      </button>
    </div>
    <label v-for="room in rooms" :key="room.id" :class="rowClass">
      <input v-model="selectedRooms" type="checkbox" :class="checkboxClass" :value="room.id">
      <span class="h-[7px] w-[7px] flex-none rounded-full border-2 border-[#94a2b2]" />
      {{ room.name }}
    </label>

    <div class="mt-3 flex items-center gap-[9px] rounded-lg bg-[#ecfaf4] px-[9px] py-[11px] text-[#178c6a] md:mt-[25px]">
      <Clock3 :size="16" class="flex-none" />
      <div class="flex flex-col gap-[3px]">
        <strong class="text-[10px]">{{ freeSlotsToday }} horários livres</strong>
        <span class="text-[9px] text-[#67ae96]">disponíveis hoje</span>
      </div>
    </div>
  </aside>
</template>
