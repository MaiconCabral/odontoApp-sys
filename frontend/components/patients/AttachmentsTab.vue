<script setup lang="ts">
import { Download, FileText, Trash2, Upload } from 'lucide-vue-next'
import type { PatientAttachment } from '~/types/clinical'

const props = defineProps<{
  patientId: string
}>()

const { showToast } = useToast()

const ACCEPTED = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp']
const MAX_BYTES = 10 * 1024 * 1024

const attachments = ref<PatientAttachment[]>([])
const loadFailed = ref(false)
const isUploading = ref(false)
const uploadError = ref('')
const isDragging = ref(false)
const confirmingDelete = ref<string | null>(null)
const input = ref<HTMLInputElement | null>(null)

// Files of the patient.
async function load() {
  loadFailed.value = false

  try {
    attachments.value = await clinicalService.attachments(props.patientId)
  }
  catch {
    loadFailed.value = true
  }
}

await load()

// Checks type and size before sending; the API checks the content again.
function problemWith(file: File) {
  if (!ACCEPTED.includes(file.type)) {
    return 'Envie um PDF ou uma imagem (JPG, PNG ou WebP).'
  }

  return file.size > MAX_BYTES ? 'O arquivo pode ter até 10 MB.' : ''
}

// Sends the files one at a time, so one refused file does not stop the others.
async function send(files: FileList | File[]) {
  uploadError.value = ''
  isUploading.value = true

  for (const file of Array.from(files)) {
    const problem = problemWith(file)

    if (problem) {
      uploadError.value = `${file.name}: ${problem}`
      continue
    }

    try {
      attachments.value.unshift(await clinicalService.upload(props.patientId, file))
      showToast(`${file.name} foi anexado.`)
    }
    catch (failure) {
      const { message, fields } = parseApiError(failure)

      uploadError.value = `${file.name}: ${fields.file ?? message}`
    }
  }

  isUploading.value = false

  if (input.value) {
    input.value.value = ''
  }
}

// Files dropped on the area.
function drop(event: DragEvent) {
  isDragging.value = false

  if (event.dataTransfer?.files.length) {
    send(event.dataTransfer.files)
  }
}

// Deletes a file after a second click.
async function remove(attachment: PatientAttachment) {
  if (confirmingDelete.value !== attachment.id) {
    confirmingDelete.value = attachment.id

    return
  }

  confirmingDelete.value = null

  try {
    await clinicalService.removeAttachment(attachment.id)
    attachments.value = attachments.value.filter(item => item.id !== attachment.id)
    showToast(`${attachment.name} foi removido.`)
  }
  catch (failure) {
    showToast(parseApiError(failure).message)
  }
}

// Size as "320 KB" or "2,4 MB".
function formatSize(bytes: number) {
  return bytes < 1024 * 1024
    ? `${Math.max(1, Math.round(bytes / 1024))} KB`
    : `${(bytes / 1024 / 1024).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} MB`
}
</script>

<template>
  <SettingsCard title="Anexos" subtitle="Radiografias, exames e documentos. PDF ou imagem, até 10 MB cada.">
    <div
      class="mt-4 flex flex-col items-center gap-2 rounded-lg border border-dashed p-5 text-center text-[11px] text-muted transition duration-200"
      :class="isDragging ? 'border-brand-accent bg-brand-tint' : 'border-line'"
      data-testid="drop-zone"
      @dragover.prevent="isDragging = true"
      @dragleave="isDragging = false"
      @drop.prevent="drop"
    >
      <Upload :size="18" class="text-faint" />
      <span>Arraste os arquivos para cá ou</span>
      <label class="cursor-pointer font-bold text-brand-text">
        escolha no computador
        <input
          ref="input"
          type="file"
          class="sr-only"
          multiple
          accept=".pdf,.jpg,.jpeg,.png,.webp"
          :disabled="isUploading"
          @change="send(($event.target as HTMLInputElement).files ?? [])"
        >
      </label>
      <span v-if="isUploading" role="status">Enviando...</span>
    </div>

    <p v-if="uploadError" role="alert" class="mt-2 text-[11px] text-tone-red-text">
      {{ uploadError }}
    </p>

    <p v-if="loadFailed" role="alert" class="pt-4 text-[11px] text-muted">
      Não foi possível carregar os anexos.
      <button type="button" class="font-bold text-brand-text" @click="load">
        Tentar novamente
      </button>
    </p>

    <p v-else-if="!attachments.length" class="pt-4 text-[11px] text-muted">
      Nenhum arquivo anexado.
    </p>

    <ul v-else class="mt-3">
      <li v-for="attachment in attachments" :key="attachment.id" class="flex items-center gap-3 border-b border-line-soft py-2.5 text-[11px] text-body last:border-b-0">
        <FileText :size="16" class="flex-none text-faint" />
        <span class="min-w-0 flex-1">
          <strong class="block truncate">{{ attachment.name }}</strong>
          <span class="text-[10px] text-muted">
            {{ formatSize(attachment.sizeBytes) }} · {{ attachment.uploader }} · {{ new Date(attachment.createdAt).toLocaleDateString('pt-BR') }}
          </span>
        </span>
        <a
          :href="clinicalService.downloadUrl(attachment.id)"
          class="grid h-[30px] w-[30px] place-items-center rounded-lg text-muted hover:bg-line-soft hover:text-brand-text"
          :aria-label="`Baixar ${attachment.name}`"
        >
          <Download :size="14" />
        </a>
        <button
          v-if="attachment.canDelete"
          type="button"
          class="grid h-[30px] min-w-[30px] place-items-center rounded-lg text-muted hover:bg-line-soft hover:text-brand-text"
          :aria-label="confirmingDelete === attachment.id ? `Confirmar exclusão de ${attachment.name}` : `Excluir ${attachment.name}`"
          @click="remove(attachment)"
        >
          <span v-if="confirmingDelete === attachment.id" class="text-[10px] font-bold text-tone-red-text">Sim?</span>
          <Trash2 v-else :size="14" />
        </button>
      </li>
    </ul>
  </SettingsCard>
</template>
