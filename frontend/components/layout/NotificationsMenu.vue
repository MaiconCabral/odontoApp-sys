<script setup lang="ts">
import { Bell } from 'lucide-vue-next'
import type { StaffNotification } from '~/types/notifications'

const props = defineProps<{
  buttonClass: string
}>()

// How often the bell asks the API for new alerts.
const POLL_MS = 60_000

const { state, load, markRead, markAllRead } = useNotifications()
const { openModal } = useActiveModal()
const open = ref(false)
const root = ref<HTMLElement | null>(null)
const panelId = useId()
let timer: ReturnType<typeof setInterval> | undefined

// Loads now, keeps polling and listens for clicks outside the menu.
onMounted(() => {
  load()
  timer = setInterval(load, POLL_MS)
  document.addEventListener('click', closeOutside)
})

// Stops the polling and the listener when the top bar leaves the screen.
onBeforeUnmount(() => {
  clearInterval(timer)
  document.removeEventListener('click', closeOutside)
})

// Closes the menu when the click lands outside the bell and the list.
function closeOutside(event: MouseEvent) {
  if (open.value && root.value && !root.value.contains(event.target as Node)) {
    open.value = false
  }
}

// Opens or closes the list, refreshing it on open.
function toggle() {
  open.value = !open.value

  if (open.value) {
    load()
  }
}

// Marks the alert as read and opens the appointment it is about.
function select(notification: StaffNotification) {
  markRead(notification.id)
  open.value = false

  if (notification.appointmentId) {
    openModal('appointment-details', { id: notification.appointmentId })
  }
}

// "agora", "há 5 min", "há 3 h" or the date.
function since(iso: string): string {
  const minutes = Math.floor((Date.now() - new Date(iso).getTime()) / 60_000)

  if (minutes < 1) {
    return 'agora'
  }

  if (minutes < 60) {
    return `há ${minutes} min`
  }

  if (minutes < 24 * 60) {
    return `há ${Math.floor(minutes / 60)} h`
  }

  return new Date(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
}

// Accessible name of the bell, with the unread count.
const label = computed(() => state.value.unreadCount
  ? `Notificações, ${state.value.unreadCount} não lida(s)`
  : 'Notificações')
</script>

<template>
  <div ref="root" class="relative">
    <button
      type="button"
      :class="[props.buttonClass, 'relative']"
      :aria-label="label"
      :aria-expanded="open"
      :aria-controls="panelId"
      @click="toggle"
    >
      <Bell :size="18" />
      <span
        v-if="state.unreadCount > 0"
        data-testid="unread-dot"
        class="absolute right-[7px] top-[7px] h-[5px] w-[5px] rounded-full border-[1.5px] border-white bg-[#ec6d58]"
      />
    </button>

    <div
      v-if="open"
      :id="panelId"
      class="absolute right-0 top-[42px] z-30 w-[min(330px,calc(100vw-24px))] overflow-hidden rounded-xl border border-line bg-surface shadow-[0_18px_45px_rgba(15,39,57,.16)]"
    >
      <div class="flex items-center justify-between border-b border-line-soft px-4 py-3">
        <strong class="text-[12px] text-ink">Notificações</strong>
        <button
          v-if="state.unreadCount > 0"
          type="button"
          class="text-[10px] font-bold text-brand-text"
          @click="markAllRead"
        >
          Marcar todas como lidas
        </button>
      </div>

      <p v-if="!state.items.length" class="px-4 py-6 text-center text-[11px] text-muted">
        Nenhuma notificação por enquanto.
      </p>

      <ul v-else class="max-h-[360px] overflow-auto">
        <li v-for="notification in state.items" :key="notification.id" class="border-b border-line-soft last:border-b-0">
          <button
            type="button"
            class="flex w-full gap-2.5 px-4 py-3 text-left transition duration-200 hover:bg-line-soft dark:hover:bg-brand-soft"
            :class="{ 'bg-brand-soft/40': !notification.read }"
            @click="select(notification)"
          >
            <span
              class="mt-1 h-1.5 w-1.5 flex-none rounded-full"
              :class="notification.read ? 'bg-transparent' : 'bg-brand'"
              aria-hidden="true"
            />
            <span class="flex min-w-0 flex-col gap-0.5">
              <strong class="text-[11px] text-body">{{ notification.title }}</strong>
              <span class="text-[10px] text-muted">{{ notification.body }}</span>
              <span class="text-[9px] text-faint">{{ since(notification.createdAt) }}</span>
            </span>
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>
