<script setup lang="ts">
import { X } from 'lucide-vue-next'

defineProps<{
  eyebrow: string
  title: string
  description: string
  submitLabel: string
}>()

const emit = defineEmits<{
  close: []
  submit: []
}>()

const dialog = ref<HTMLDialogElement | null>(null)
const titleId = useId()

// The native dialog traps focus, handles Esc and restores focus when closed.
onMounted(() => dialog.value?.showModal())
onBeforeUnmount(() => dialog.value?.close())

function closeOnBackdrop(event: MouseEvent) {
  if (event.target === dialog.value) {
    emit('close')
  }
}
</script>

<template>
  <dialog
    ref="dialog"
    class="m-auto max-h-[90vh] w-[min(570px,calc(100%-40px))] max-w-none overflow-auto rounded-[14px] border border-[#e4ebf1] bg-surface text-ink shadow-[0_24px_65px_rgba(15,39,57,.2)] backdrop:bg-[rgba(18,30,46,.42)] backdrop:backdrop-blur-[3px] dark:border-line"
    :aria-labelledby="titleId"
    @cancel.prevent="emit('close')"
    @click="closeOnBackdrop"
  >
    <div class="flex justify-between border-b border-[#edf1f4] px-[18px] pb-[18px] pt-[30px] dark:border-line sm:px-[25px]">
      <div>
        <div class="text-[10px] font-bold uppercase tracking-[.08em] text-[#118eaa]">
          {{ eyebrow }}
        </div>
        <h2 :id="titleId" class="mb-[5px] mt-2 font-display text-[20px] font-extrabold leading-[normal] tracking-[-.6px] text-ink">
          {{ title }}
        </h2>
        <p class="text-[11px] text-[#95a1b0] dark:text-muted">
          {{ description }}
        </p>
      </div>
      <button
        type="button"
        class="-mt-1.5 grid h-[34px] w-[34px] flex-none place-items-center rounded-lg text-[#738095] transition duration-200 hover:bg-[#f1f5f9] hover:text-[#167d9d] dark:hover:bg-[#172b37]"
        aria-label="Fechar"
        @click="emit('close')"
      >
        <X :size="19" />
      </button>
    </div>

    <form novalidate @submit.prevent="emit('submit')">
      <div class="grid grid-cols-1 gap-x-[13px] gap-y-4 px-[18px] py-[21px] sm:grid-cols-2 sm:px-[25px]">
        <slot />
      </div>
      <div class="flex justify-end gap-[9px] border-t border-[#edf1f4] px-[18px] pb-[21px] pt-[15px] dark:border-line sm:px-[25px]">
        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#e3e9f0] bg-surface px-[15px] py-2.5 text-[12px] font-bold text-[#647286] transition duration-200 dark:border-line dark:text-[#acb8c8]"
          @click="emit('close')"
        >
          Cancelar
        </button>
        <button
          type="submit"
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-2.5 py-2.5 text-[12px] font-bold text-white shadow-[0_5px_14px_rgba(11,140,173,.18)] transition duration-200 hover:-translate-y-px hover:bg-brand-hover sm:px-[15px]"
        >
          <slot name="submit-icon" />
          {{ submitLabel }}
        </button>
      </div>
    </form>
  </dialog>
</template>
