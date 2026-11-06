import { defineComponent, h } from 'vue'

// happy-dom cannot draw charts. The stub keeps the props, so tests read what would be drawn.
// It replaces vue3-apexcharts through an alias in vitest.config.ts, so even the lazy import in
// AppChart never reaches the real library (which throws "Element not found" in happy-dom).
export default defineComponent({
  name: 'ApexChartStub',
  props: ['type', 'series', 'options', 'height'],
  setup: props => () => h('div', { 'data-testid': 'apex-chart', 'data-type': props.type }),
})
