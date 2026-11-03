<script setup lang="ts">
import { ChevronDown, ChevronRight, Menu, Moon, Sun } from 'lucide-vue-next'

defineProps<{
  menuOpen: boolean
}>()

defineEmits<{
  openMenu: []
}>()

const route = useRoute()
const workspace = useWorkspace()
const { isDark, toggleTheme } = useTheme()

const pageLabel = computed(() => workspace.value.nav.find(item => item.path === route.path)?.label)

const iconButtonClass = 'grid h-[34px] w-[34px] place-items-center rounded-lg text-muted transition duration-200 hover:bg-line-soft hover:text-brand-text dark:hover:bg-brand-soft'
</script>

<template>
  <header class="flex h-[65px] items-center justify-between border-b border-line bg-[rgba(255,255,255,.76)] px-[18px] dark:bg-[rgba(24,24,27,.82)] md:h-[74px] md:px-[26px] lg:px-10">
    <div class="flex items-center gap-2 md:gap-0">
      <button
        type="button"
        :class="[iconButtonClass, 'md:hidden']"
        aria-label="Abrir menu"
        :aria-expanded="menuOpen"
        @click="$emit('openMenu')"
      >
        <Menu :size="20" />
      </button>
      <div class="flex items-center gap-2 text-[12px] text-faint">
        <span class="max-[389px]:hidden">Workspace</span>
        <ChevronRight :size="14" class="max-[389px]:hidden" />
        <strong class="font-semibold text-body">{{ pageLabel }}</strong>
      </div>
    </div>

    <div class="flex items-center gap-[3px] sm:gap-2.5">
      <div class="flex items-center gap-[7px] rounded-[7px] p-[7px] text-[0px] text-muted sm:border sm:border-line sm:px-2.5 sm:text-[11px]">
        <span class="mr-[3px] h-1.5 w-1.5 rounded-full bg-[#32bc8a] shadow-[0_0_0_3px_#e1f7ee] sm:mr-0" />
        Trial {{ workspace.plan.name }}
        <span class="hidden font-semibold text-brand-text md:inline">{{ workspace.plan.trialDaysLeft }} dias restantes</span>
      </div>
      <button type="button" :class="iconButtonClass" aria-label="Alternar tema" @click="toggleTheme">
        <Sun v-if="isDark" :size="18" />
        <Moon v-else :size="18" />
      </button>
      <LayoutNotificationsMenu :button-class="iconButtonClass" />
      <div class="flex items-center gap-[9px] border-line md:border-l md:pl-3">
        <div class="grid h-[31px] w-[31px] place-items-center rounded-[9px] bg-brand-soft text-[10px] font-bold text-brand-text">
          {{ workspace.user.initials }}
        </div>
        <div class="hidden flex-col gap-0.5 md:flex">
          <strong class="text-[11px] text-body">{{ workspace.user.name }}</strong>
          <span class="text-[10px] text-muted">{{ workspace.user.roleLabel }}</span>
        </div>
        <ChevronDown :size="15" class="hidden text-faint md:block" />
      </div>
    </div>
  </header>
</template>
