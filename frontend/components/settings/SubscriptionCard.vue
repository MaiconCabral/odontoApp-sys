<script setup lang="ts">
import { Check, Sparkles } from 'lucide-vue-next'
import type { Subscription } from '~/types/settings'

defineProps<{
  subscription: Subscription
}>()

const workspace = useWorkspace()
</script>

<template>
  <div class="max-w-[600px] rounded-xl border border-line bg-surface p-4 shadow-[0_4px_14px_rgba(42,64,91,.025)] sm:p-[22px]">
    <div class="flex items-center gap-3">
      <div class="grid h-[30px] w-[30px] flex-none place-items-center rounded-lg bg-brand-soft text-brand-text">
        <Sparkles :size="16" />
      </div>
      <div>
        <h2 class="mb-0.5 mt-1 font-display text-[19px] font-bold leading-[normal] text-ink">
          Plano {{ workspace.plan.name }}
        </h2>
        <p class="text-[11px] text-muted">
          Seu trial termina em {{ workspace.plan.trialDaysLeft }} dias.
        </p>
      </div>
      <span class="ml-auto flex-none rounded-[5px] bg-[#e5f7ef] px-2 py-[5px] text-[9px] font-bold text-[#258f6c]">
        {{ subscription.status }}
      </span>
    </div>

    <div class="mt-7 flex items-baseline">
      <strong class="font-display text-[35px] font-extrabold leading-[normal] tracking-[-1.5px]">{{ subscription.price }}</strong>
      <span class="text-[11px] text-muted">{{ subscription.period }}</span>
    </div>

    <ul class="mb-[23px] mt-4 flex flex-wrap gap-[13px] text-[10px] text-body">
      <li v-for="feature in subscription.features" :key="feature" class="flex items-center gap-1">
        <Check :size="13" class="flex-none text-[#24a375]" />
        {{ feature }}
      </li>
    </ul>

    <button
      type="button"
      class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-[15px] py-2.5 text-[12px] font-bold text-white shadow-[0_5px_14px_rgba(2,132,199,.18)] transition duration-200 hover:-translate-y-px hover:bg-brand-hover"
    >
      Gerenciar assinatura
    </button>
  </div>
</template>
