<script setup lang="ts">
import type { ToothRecord, ToothSurface } from '~/types/clinical'

const props = defineProps<{
  records: ToothRecord[]
  deciduous: boolean
  selected: number | null
}>()

const emit = defineEmits<{
  select: [tooth: number, surface: ToothSurface | null]
}>()

const rows = computed(() => (props.deciduous ? DECIDUOUS_ROWS : PERMANENT_ROWS))

// Fill class of one surface of a tooth.
function fillOf(tooth: number, surface: ToothSurface) {
  const record = toothState(props.records, tooth).surfaces[surface]

  return (record && SURFACE_FILL[record.condition]?.fill) ?? 'fill-surface'
}

// Mark drawn under a tooth with a whole-tooth condition.
function markOf(tooth: number) {
  const whole = toothState(props.records, tooth).whole

  return whole ? TOOTH_MARK[whole.condition] ?? null : null
}

// The five surfaces as polygons of a 30x30 square: four trapezoids around a center square.
const shapes = {
  top: '0,0 30,0 21,9 9,9',
  bottom: '9,21 21,21 30,30 0,30',
  left: '0,0 9,9 9,21 0,30',
  right: '30,0 30,30 21,21 21,9',
} as const
</script>

<template>
  <div class="overflow-x-auto">
    <div class="mx-auto grid w-max gap-5 py-2">
      <div v-for="(row, index) in rows" :key="index" class="flex gap-1">
        <template v-for="(tooth, position) in row" :key="tooth">
          <span v-if="position === row.length / 2" class="w-2 border-l border-dashed border-line" aria-hidden="true" />
          <button
            type="button"
            class="group flex flex-col items-center gap-0.5 rounded-md p-0.5 outline-none focus-visible:ring-2 focus-visible:ring-brand-accent"
            :class="{ 'bg-brand-tint': selected === tooth }"
            :aria-label="toothLabel(records, tooth)"
            :aria-pressed="selected === tooth"
            @click="emit('select', tooth, null)"
          >
            <span v-if="index === 0" class="text-[9px] font-semibold text-muted" aria-hidden="true">{{ tooth }}</span>
            <svg
              viewBox="0 0 30 30"
              class="h-[30px] w-[30px] stroke-line"
              :class="{ 'opacity-40': markOf(tooth)?.letter === '—' }"
              aria-hidden="true"
            >
              <polygon
                v-for="(points, side) in shapes"
                :key="side"
                :points="points"
                stroke-width="1"
                class="cursor-pointer transition duration-150 group-hover:stroke-brand-accent"
                :class="fillOf(tooth, surfaceSides(tooth)[side])"
                @click.stop="emit('select', tooth, surfaceSides(tooth)[side])"
              />
              <rect
                x="9"
                y="9"
                width="12"
                height="12"
                stroke-width="1"
                class="cursor-pointer transition duration-150 group-hover:stroke-brand-accent"
                :class="fillOf(tooth, 'O')"
                @click.stop="emit('select', tooth, 'O')"
              />
            </svg>
            <span class="h-3 text-[10px] font-bold leading-3" :class="markOf(tooth)?.class" aria-hidden="true">{{ markOf(tooth)?.letter ?? '' }}</span>
            <span v-if="index === rows.length - 1" class="text-[9px] font-semibold text-muted" aria-hidden="true">{{ tooth }}</span>
          </button>
        </template>
      </div>
    </div>
  </div>
</template>
