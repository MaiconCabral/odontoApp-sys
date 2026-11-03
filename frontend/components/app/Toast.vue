<script setup lang="ts">
const { message, hideToast } = useToast()

let timer: ReturnType<typeof setTimeout> | undefined

watch(message, (text) => {
  clearTimeout(timer)

  if (text) {
    timer = setTimeout(hideToast, 4000)
  }
})

onBeforeUnmount(() => clearTimeout(timer))
</script>

<template>
  <div
    v-if="message"
    role="status"
    class="fixed bottom-[15px] left-[15px] right-[15px] z-[70] flex items-center gap-[9px] rounded-[9px] border border-tone-green-line bg-tone-green-bg py-3 pl-3 pr-4 text-[11px] text-tone-green-text shadow-[0_12px_28px_rgba(26,65,55,.12)] sm:bottom-6 sm:left-auto sm:right-7"
  >
    <span class="grid h-5 w-5 flex-none place-items-center rounded-full bg-tone-green-solid font-bold text-white" aria-hidden="true">✓</span>
    {{ message }}
  </div>
</template>
