<script setup lang="ts">
import {
  ArrowUpRight,
  ChartColumn,
  CalendarDays,
  ChevronDown,
  CircleHelp,
  Hourglass,
  LayoutDashboard,
  LogOut,
  PanelLeftClose,
  PanelLeftOpen,
  Settings,
  Sparkles,
  Users,
  X,
} from 'lucide-vue-next'
import type { NavIcon } from '~/types/workspace'

const props = defineProps<{
  collapsed: boolean
  mobileOpen: boolean
}>()

defineEmits<{
  close: []
  toggleCollapse: []
}>()

const navIcons = {
  overview: LayoutDashboard,
  agenda: CalendarDays,
  patients: Users,
  waitlist: Hourglass,
  reports: ChartColumn,
  settings: Settings,
} satisfies Record<NavIcon, unknown>

const route = useRoute()
const workspace = useWorkspace()
const { logout } = useAuth()

// The entry of the current page, also on its subpages (a patient page keeps "Pacientes" marked).
function isCurrent(path: string) {
  return route.path === path || (path !== panelPath() && route.path.startsWith(`${path}/`))
}

// The mobile drawer always shows the full menu.
const isCompact = computed(() => props.collapsed && !props.mobileOpen)

const footerItemClass = computed(() => [
  'flex w-full items-center gap-3 rounded-[9px] px-3 py-2.5 text-left text-[12px] text-muted transition duration-200 hover:bg-brand-tint hover:text-brand-text dark:hover:bg-brand-soft',
  isCompact.value ? 'h-[42px] justify-center !px-0' : '',
])
</script>

<template>
  <aside
    class="fixed inset-y-0 left-0 z-20 flex w-[252px] flex-none flex-col border-r border-line bg-surface px-3.5 pb-3.5 pt-[22px] transition-[width,transform] duration-[250ms] ease-in-out md:static md:translate-x-0 md:shadow-none"
    :class="[
      mobileOpen ? 'translate-x-0 shadow-[15px_0_35px_rgba(13,35,55,.12)]' : '-translate-x-full',
      isCompact ? 'md:w-[76px]' : 'md:w-[220px] lg:w-[252px]',
    ]"
    data-testid="sidebar"
  >
    <div class="flex h-[34px] items-center gap-2.5" :class="isCompact ? 'justify-center' : 'px-2.5'">
      <AppLogo :compact="isCompact" />
      <button
        type="button"
        class="ml-auto grid h-[34px] w-[34px] place-items-center rounded-lg text-muted transition duration-200 hover:bg-line-soft hover:text-brand-text dark:hover:bg-brand-soft md:hidden"
        aria-label="Fechar menu"
        @click="$emit('close')"
      >
        <X :size="19" />
      </button>
    </div>

    <div
      class="mx-1 mb-[30px] mt-[29px] flex items-center gap-2.5 rounded-xl border border-line bg-surface-soft"
      :class="isCompact ? 'justify-center p-2' : 'p-2.5'"
    >
      <div class="grid h-8 w-8 flex-none place-items-center rounded-[9px] bg-brand-soft text-[11px] font-bold text-brand-text">
        {{ workspace.clinic.initials }}
      </div>
      <template v-if="!isCompact">
        <div class="flex min-w-0 flex-1 flex-col">
          <strong class="truncate text-[12px]">{{ workspace.clinic.name }}</strong>
          <span class="mt-0.5 text-[10px] text-muted">{{ workspace.clinic.unit }}</span>
        </div>
        <ChevronDown :size="16" class="text-faint" />
      </template>
    </div>

    <div
      class="pb-[9px] font-bold uppercase tracking-[.1em] text-faint"
      :class="isCompact ? 'text-center text-[8px]' : 'px-3 text-[10px]'"
    >
      Workspace
    </div>
    <nav class="grid gap-[5px]" aria-label="Principal">
      <NuxtLink
        v-for="item in workspace.nav"
        :key="item.id"
        :to="item.path"
        class="flex w-full items-center gap-3 rounded-[9px] py-2.5 text-left text-[13px] transition duration-200 hover:bg-brand-tint hover:text-brand-text dark:hover:bg-brand-soft"
        :class="[
          isCurrent(item.path)
            ? 'bg-brand-soft font-bold text-brand-text'
            : 'text-muted',
          isCompact ? 'h-[42px] justify-center' : 'px-3',
        ]"
        :aria-current="isCurrent(item.path) ? 'page' : undefined"
        :aria-label="isCompact ? item.label : undefined"
      >
        <component
          :is="navIcons[item.icon]"
          :size="18"
          :stroke-width="isCurrent(item.path) ? 2.4 : 1.9"
          class="flex-none"
        />
        <template v-if="!isCompact">
          <span>{{ item.label }}</span>
          <span
            v-if="item.count"
            class="ml-auto grid h-[19px] min-w-5 place-items-center rounded-md bg-brand-soft text-[10px] text-brand-text"
          >
            {{ item.count }}
          </span>
        </template>
      </NuxtLink>
    </nav>

    <div class="mt-auto">
      <div
        v-if="!isCompact"
        class="mx-1 mb-[17px] mt-5 rounded-xl border border-brand-line bg-brand-tint p-3.5"
      >
        <div class="grid h-[30px] w-[30px] place-items-center rounded-lg bg-brand-soft text-brand-text">
          <Sparkles :size="16" />
        </div>
        <strong class="mt-[11px] block text-[12px]">Plano {{ workspace.plan.name }}</strong>
        <p class="mb-2.5 mt-1 text-[11px] leading-[1.45] text-muted">
          Seu trial termina em <b class="text-brand-text">{{ workspace.plan.trialDaysLeft }} dias</b>.
        </p>
        <button type="button" class="flex items-center gap-1 text-[11px] font-bold text-brand-text">
          Ver detalhes <ArrowUpRight :size="13" />
        </button>
      </div>

      <button type="button" :class="footerItemClass" :aria-label="isCompact ? 'Central de ajuda' : undefined">
        <CircleHelp :size="18" class="flex-none" />
        <span v-if="!isCompact">Central de ajuda</span>
      </button>
      <button type="button" :class="footerItemClass" :aria-label="isCompact ? 'Sair da conta' : undefined" @click="logout">
        <LogOut :size="18" class="flex-none" />
        <span v-if="!isCompact">Sair da conta</span>
      </button>
    </div>

    <button
      type="button"
      class="mt-[15px] hidden w-full items-center gap-[11px] py-2.5 text-[11px] text-faint transition duration-200 hover:text-brand-text md:flex"
      :class="isCompact ? 'justify-center' : 'px-3'"
      :aria-label="isCompact ? 'Expandir menu' : undefined"
      :aria-expanded="!isCompact"
      @click="$emit('toggleCollapse')"
    >
      <PanelLeftOpen v-if="isCompact" :size="18" />
      <template v-else>
        <PanelLeftClose :size="18" /> Recolher menu
      </template>
    </button>
  </aside>
</template>
