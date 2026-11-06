<script setup lang="ts">
import type { ToothRecord, ToothSurface } from '~/types/clinical'
import type { ToothState } from '~/utils/odontogram'

const props = defineProps<{
  records: ToothRecord[]
  deciduous: boolean
  selected: number | null
}>()

const emit = defineEmits<{
  select: [tooth: number, surface: ToothSurface | null]
}>()

const rows = computed(() => (props.deciduous ? DECIDUOUS_ROWS : PERMANENT_ROWS))

// Everything one tooth draws, computed once per change of the records instead of on every
// render: selecting a tooth re-renders the chart but does not scan the records again.
const teeth = computed(() => {
  const states = toothStates(props.records)
  const empty: ToothState = { whole: null, surfaces: {} }

  return new Map(rows.value.flat().map((tooth) => {
    const state = states.get(tooth) ?? empty
    const sides = surfaceSides(tooth)
    const fill = (surface: ToothSurface) => {
      const record = state.surfaces[surface]

      return (record && SURFACE_FILL[record.condition]?.fill) ?? 'fill-surface'
    }

    return [tooth, {
      label: describeTooth(state, tooth),
      mark: state.whole ? TOOTH_MARK[state.whole.condition] ?? null : null,
      sides,
      fills: { top: fill(sides.top), bottom: fill(sides.bottom), left: fill(sides.left), right: fill(sides.right), center: fill('O') },
    }]
  }))
})

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
            :aria-label="teeth.get(tooth)?.label"
            :aria-pressed="selected === tooth"
            @click="emit('select', tooth, null)"
          >
            <span v-if="index === 0" class="text-[9px] font-semibold text-muted" aria-hidden="true">{{ tooth }}</span>
            <svg
              viewBox="0 0 30 30"
              class="h-[30px] w-[30px] stroke-line"
              :class="{ 'opacity-40': teeth.get(tooth)?.mark?.letter === '—' }"
              aria-hidden="true"
            >
              <polygon
                v-for="(points, side) in shapes"
                :key="side"
                :points="points"
                stroke-width="1"
                class="cursor-pointer transition duration-150 group-hover:stroke-brand-accent"
                :class="teeth.get(tooth)?.fills[side]"
                @click.stop="emit('select', tooth, teeth.get(tooth)!.sides[side])"
              />
              <rect
                x="9"
                y="9"
                width="12"
                height="12"
                stroke-width="1"
                class="cursor-pointer transition duration-150 group-hover:stroke-brand-accent"
                :class="teeth.get(tooth)?.fills.center"
                @click.stop="emit('select', tooth, 'O')"
              />
            </svg>
            <span class="h-3 text-[10px] font-bold leading-3" :class="teeth.get(tooth)?.mark?.class" aria-hidden="true">{{ teeth.get(tooth)?.mark?.letter ?? '' }}</span>
            <span v-if="index === rows.length - 1" class="text-[9px] font-semibold text-muted" aria-hidden="true">{{ tooth }}</span>
          </button>
        </template>
      </div>
    </div>
  </div>
</template>
