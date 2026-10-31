<script setup lang="ts">
import { Ellipsis } from 'lucide-vue-next'

const props = defineProps<{
  percentage: number
}>()

const donutStyle = computed(() => ({
  background: `conic-gradient(#1599b7 0 ${props.percentage}%, #edf1f4 ${props.percentage}% 100%)`,
}))
</script>

<template>
  <OverviewPanel
    class="p-4 sm:p-[21px]"
    title="Ocupação da agenda"
    subtitle="Capacidade média dos profissionais"
  >
    <template #action>
      <Ellipsis :size="18" class="flex-none text-[#a1abba] dark:text-faint" />
    </template>

    <div class="grid place-items-center pb-5 pt-[27px]">
      <div
        class="grid h-[134px] w-[134px] place-items-center rounded-full"
        role="img"
        :aria-label="`${percentage}% da agenda ocupada`"
        :style="donutStyle"
      >
        <div class="flex h-[99px] w-[99px] flex-col items-center justify-center rounded-full bg-surface">
          <strong class="font-display text-[25px] font-extrabold leading-[normal] tracking-[-1px]">{{ percentage }}%</strong>
          <span class="text-[10px] text-[#98a4b2]">ocupada</span>
        </div>
      </div>
    </div>

    <div class="grid gap-2.5">
      <span class="flex items-center gap-[7px] text-[10px] text-[#7b889a]">
        <i class="h-[7px] w-[7px] rounded-sm bg-[#1599b7]" />
        Consultas agendadas
        <b class="ml-auto text-[#4b596b] dark:text-body">{{ percentage }}%</b>
      </span>
      <span class="flex items-center gap-[7px] text-[10px] text-[#7b889a]">
        <i class="h-[7px] w-[7px] rounded-sm bg-[#dce4e9]" />
        Horários livres
        <b class="ml-auto text-[#4b596b] dark:text-body">{{ 100 - percentage }}%</b>
      </span>
    </div>

    <OverviewTextButton class="mt-5" to="/agenda">
      Ver agenda completa
    </OverviewTextButton>
  </OverviewPanel>
</template>
