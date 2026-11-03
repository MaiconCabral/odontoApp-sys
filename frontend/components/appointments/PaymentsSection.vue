<script setup lang="ts">
import type { AppointmentStatus } from '~/types/agenda'
import type { Payment, PaymentForm, PaymentMethod, PaymentSummary } from '~/types/finance'

const props = defineProps<{
  appointmentId: string
  status: AppointmentStatus
}>()

const emit = defineEmits<{
  changed: []
}>()

const { showToast } = useToast()

const METHODS: [PaymentMethod, string][] = [
  ['pix', 'Pix'],
  ['credit_card', 'Cartão de crédito'],
  ['debit_card', 'Cartão de débito'],
  ['cash', 'Dinheiro'],
  ['insurance', 'Convênio'],
  ['other', 'Outro'],
]

const summary = ref<PaymentSummary | null>(null)
const loadFailed = ref(false)

// Payments of the appointment with what is left to pay.
async function load() {
  loadFailed.value = false

  try {
    summary.value = await financeService.payments(props.appointmentId)
  }
  catch {
    loadFailed.value = true
  }
}

await load()

// Requests, cancellations and rejections are not charged (same rule as the API).
const canCharge = computed(() => !['requested', 'cancelled', 'rejected'].includes(props.status))

const isFormOpen = ref(false)
const form = reactive<PaymentForm>({ amount: '', method: 'pix', paidAt: localToday(), notes: '', allowOver: false })

// Opens the form with the balance as the amount.
function openForm() {
  form.amount = summary.value?.balanceCents ? centsToInput(summary.value.balanceCents) : ''
  isFormOpen.value = true
}

// Amount typed as a price and a date that is not in the future.
function validatePayment(fields: PaymentForm) {
  const cents = toCents(fields.amount)

  if (cents === null || cents <= 0) {
    return { amount: 'Informe um valor maior que zero.' }
  }

  return fields.paidAt > localToday() ? { paidAt: 'A data do pagamento não pode estar no futuro.' } : {}
}

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validatePayment, async () => {
  summary.value = await financeService.recordPayment(props.appointmentId, { ...form })
  isFormOpen.value = false
  form.notes = ''
  form.allowOver = false
  showToast('Pagamento registrado.')
  emit('changed')
})

const voiding = ref<string | null>(null)
const voidReason = ref('')
const voidError = ref('')

// Voids a payment after asking the reason.
async function confirmVoid(payment: Payment) {
  voidError.value = ''

  try {
    summary.value = await financeService.voidPayment(payment.id, voidReason.value.trim())
    voiding.value = null
    voidReason.value = ''
    showToast('Pagamento estornado.')
    emit('changed')
  }
  catch (failure) {
    const { message, fields } = parseApiError(failure)

    voidError.value = fields.reason ?? fields.payment ?? message
  }
}

const statusLabels = { none: 'Sem cobrança', pending: 'A receber', partial: 'Pago em parte', paid: 'Pago' }
</script>

<template>
  <section class="sm:col-span-2" aria-label="Pagamentos">
    <h3 class="mb-2 text-[10px] font-bold uppercase tracking-[.06em] text-muted">
      Pagamentos
    </h3>

    <p v-if="loadFailed" role="alert" class="text-[10px] text-muted">
      Não foi possível carregar os pagamentos.
      <button type="button" class="font-bold text-brand-text" @click="load">
        Tentar novamente
      </button>
    </p>

    <template v-else-if="summary">
      <dl class="mb-2 grid grid-cols-3 gap-2 text-[11px]">
        <div>
          <dt class="text-[10px] text-muted">
            Preço
          </dt>
          <dd>{{ formatMoney(summary.priceCents) }}</dd>
        </div>
        <div>
          <dt class="text-[10px] text-muted">
            Pago
          </dt>
          <dd>{{ formatMoney(summary.paidCents) }}</dd>
        </div>
        <div>
          <dt class="text-[10px] text-muted">
            Falta
          </dt>
          <dd class="font-bold" :class="summary.balanceCents ? 'text-tone-amber-text' : 'text-tone-green-text'">
            {{ summary.balanceCents ? formatMoney(summary.balanceCents) : statusLabels[summary.paymentStatus] }}
          </dd>
        </div>
      </dl>

      <ul v-if="summary.payments.length" class="mb-2 grid gap-1 text-[10px] text-muted">
        <li v-for="payment in summary.payments" :key="payment.id" class="flex flex-wrap items-center gap-x-2">
          <span :class="{ 'line-through': payment.voided }">
            <strong class="text-body">{{ formatMoney(payment.amountCents) }}</strong>
            · {{ payment.methodLabel }} · {{ formatShortDate(payment.paidAt) }} · {{ payment.recordedBy }}
          </span>
          <span v-if="payment.voided" class="text-tone-red-text">Estornado: {{ payment.voidReason }}</span>
          <button v-if="payment.canVoid && voiding !== payment.id" type="button" class="font-bold text-tone-red-text" @click="voiding = payment.id">
            Estornar
          </button>
          <span v-if="voiding === payment.id" class="flex w-full flex-wrap items-center gap-2 pt-1">
            <input v-model="voidReason" maxlength="255" placeholder="Motivo do estorno" aria-label="Motivo do estorno" class="h-[30px] flex-1 rounded-[7px] border border-line bg-surface px-2 text-[11px] text-body">
            <button type="button" class="rounded-lg border border-line px-2 py-1 font-bold text-tone-red-text" @click="confirmVoid(payment)">
              Confirmar estorno
            </button>
            <button type="button" class="font-bold text-muted" @click="voiding = null">
              Voltar
            </button>
          </span>
        </li>
      </ul>
      <p v-if="voidError" role="alert" class="mb-2 text-[10px] text-tone-red-text">
        {{ voidError }}
      </p>

      <button
        v-if="canCharge && !isFormOpen"
        type="button"
        class="rounded-lg border border-line px-3 py-2 text-[11px] font-bold text-body"
        @click="openForm"
      >
        Registrar pagamento
      </button>

      <form v-if="isFormOpen" class="grid gap-2 rounded-lg border border-line-soft p-3 sm:grid-cols-3" novalidate @submit.prevent="submit()">
        <p v-if="formError" role="alert" class="text-[11px] text-tone-red-text sm:col-span-3">
          {{ formError }}
        </p>
        <AppFormField v-slot="field" label="Valor (R$)" :error="errors.amount">
          <input :id="field.id" v-model="form.amount" inputmode="decimal" autocomplete="off" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
        </AppFormField>
        <AppFormField v-slot="field" label="Forma">
          <select :id="field.id" v-model="form.method" :class="field.controlClass">
            <option v-for="[method, label] in METHODS" :key="method" :value="method">
              {{ label }}
            </option>
          </select>
        </AppFormField>
        <AppFormField v-slot="field" label="Data" :error="errors.paidAt">
          <input :id="field.id" v-model="form.paidAt" type="date" :max="localToday()" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
        </AppFormField>
        <AppFormField v-slot="field" label="Observação (opcional)" full>
          <input :id="field.id" v-model="form.notes" maxlength="500" :class="field.controlClass">
        </AppFormField>
        <label class="flex items-center gap-2 text-[10px] text-muted sm:col-span-3">
          <input v-model="form.allowOver" type="checkbox" class="accent-brand">
          Aceitar valor acima do preço (procedimento extra no dia)
        </label>
        <div class="flex gap-2 sm:col-span-3">
          <button type="submit" :disabled="isSubmitting" class="rounded-lg bg-brand px-3 py-2 text-[11px] font-bold text-white disabled:opacity-60">
            Registrar
          </button>
          <button type="button" class="rounded-lg border border-line px-3 py-2 text-[11px] font-bold text-body" @click="isFormOpen = false">
            Cancelar
          </button>
        </div>
      </form>
    </template>
  </section>
</template>
