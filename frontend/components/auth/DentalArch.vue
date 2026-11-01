<script setup lang="ts">
// Tooth widths from the midline back: incisors, canine, premolars, molars.
const TOOTH_WIDTHS = [13, 11, 12, 13, 13, 17, 17, 15]
const TOOTH_HEIGHT = 23
const GAP = 3
const RADIUS_X = 72
const RADIUS_Y = 88
const CENTER_X = 160
const UPPER_CENTER_Y = 170
const LOWER_CENTER_Y = 236
const MARKED_TEETH = [16, 11, 36]

// FDI quadrants as seen facing the patient.
const QUADRANTS = [
  { number: 1, side: -1, vertical: -1 },
  { number: 2, side: 1, vertical: -1 },
  { number: 3, side: 1, vertical: 1 },
  { number: 4, side: -1, vertical: 1 },
]

const AVERAGE_RADIUS = (RADIUS_X + RADIUS_Y) / 2

const teeth = QUADRANTS.flatMap(({ number, side, vertical }) => {
  let distance = GAP / 2

  return TOOTH_WIDTHS.map((width, index) => {
    const angle = (distance + width / 2) / AVERAGE_RADIUS
    const centerY = vertical < 0 ? UPPER_CENTER_Y : LOWER_CENTER_Y
    const x = CENTER_X + side * RADIUS_X * Math.sin(angle)
    const y = centerY + vertical * RADIUS_Y * Math.cos(angle)
    const fdi = number * 10 + index + 1

    distance += width + GAP

    return {
      fdi,
      width,
      isPosterior: index >= 3,
      isMarked: MARKED_TEETH.includes(fdi),
      transform: `translate(${x.toFixed(2)} ${y.toFixed(2)}) rotate(${(side * vertical * -angle * 180 / Math.PI).toFixed(2)})`,
    }
  })
})
</script>

<template>
  <svg viewBox="60 60 200 286" fill="none" class="h-auto w-full" aria-hidden="true">
    <g v-for="tooth in teeth" :key="tooth.fdi" :transform="tooth.transform">
      <rect
        :x="-tooth.width / 2"
        :y="-TOOTH_HEIGHT / 2"
        :width="tooth.width"
        :height="TOOTH_HEIGHT"
        :rx="tooth.width * .36"
        :fill="tooth.isMarked ? '#0ea5e9' : 'rgba(255,255,255,.04)'"
        :stroke="tooth.isMarked ? '#7dd3fc' : 'rgba(186,230,253,.55)'"
        stroke-width="1.1"
      />
      <path
        v-if="tooth.isPosterior"
        :d="`M0 ${-TOOTH_HEIGHT / 2 + 5}V${TOOTH_HEIGHT / 2 - 5}M${-tooth.width / 2 + 3} 0H${tooth.width / 2 - 3}`"
        :stroke="tooth.isMarked ? '#e0f2fe' : 'rgba(186,230,253,.32)'"
        stroke-width="1"
        stroke-linecap="round"
      />
    </g>
    <path d="M160 92V314" stroke="rgba(186,230,253,.14)" stroke-dasharray="2 6" />
    <path d="M78 203H242" stroke="rgba(186,230,253,.14)" stroke-dasharray="2 6" />
  </svg>
</template>
