<script setup lang="ts">
import { Check } from 'lucide-vue-next'
import type { Subscription } from '~/types/settings'

const props = defineProps<{
  subscription: Subscription
}>()

const emit = defineEmits<{
  close: []
}>()

// The plan changes by talking to the team until online billing exists.
const contactUrl = useRuntimeConfig().public.salesContactUrl

// Opens the sales contact in a new tab.
function contact() {
  window.open(contactUrl, '_blank', 'noopener')
}

// Explains how to change plan, with or without the contact button.
const description = computed(() => contactUrl
  ? 'Para mudar de plano, fale com a gente. A troca é feita pela nossa equipe.'
  : 'Para mudar de plano, entre em contato com o suporte.')

// Whether the plan is the one the clinic has today.
const isCurrent = (code: string) => code === props.subscription.plan.code
</script>

<template>
  <AppModal
    eyebrow="Assinatura"
    title="Planos"
    :description="description"
    :submit-label="contactUrl ? 'Falar com a gente' : undefined"
    @close="emit('close')"
    @submit="contact()"
  >
    <ul class="grid gap-3 sm:col-span-2 sm:grid-cols-3" aria-label="Planos disponíveis">
      <li
        v-for="plan in subscription.plans"
        :key="plan.code"
        class="flex flex-col gap-2 rounded-xl border p-4"
        :class="isCurrent(plan.code) ? 'border-brand bg-brand-soft/40' : 'border-line'"
        :aria-current="isCurrent(plan.code) ? 'true' : undefined"
      >
        <div class="flex items-center justify-between gap-2">
          <strong class="font-display text-[15px] text-ink">{{ plan.name }}</strong>
          <span v-if="isCurrent(plan.code)" class="rounded-[5px] bg-brand px-1.5 py-0.5 text-[9px] font-bold text-white">Atual</span>
        </div>
        <div class="flex items-baseline">
          <strong class="font-display text-[22px] font-extrabold tracking-[-1px]">{{ plan.price }}</strong>
          <span class="text-[10px] text-muted">{{ subscription.period }}</span>
        </div>
        <ul class="flex flex-col gap-1 text-[10px] text-body">
          <li v-for="feature in plan.features" :key="feature" class="flex items-center gap-1">
            <Check :size="12" class="flex-none text-tone-green-text" />
            {{ feature }}
          </li>
        </ul>
      </li>
    </ul>
  </AppModal>
</template>
