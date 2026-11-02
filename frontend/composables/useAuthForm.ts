import type { Ref } from 'vue'
import type { FormErrors } from '~/utils/auth'

// Validation, submit and API errors of a form.
export function useAuthForm<Form extends object>(
  form: Form,
  validate: (form: Form) => FormErrors<Form>,
  action: () => Promise<void>,
) {
  const errors = ref({}) as Ref<FormErrors<Form>>
  const formError = ref('')
  const isSubmitting = ref(false)

  // Moves the focus to the first invalid field.
  async function focusFirstInvalid(element: HTMLFormElement | null) {
    await nextTick()
    element?.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus()
  }

  // Validates and runs the form action.
  async function submit(event?: Event) {
    if (isSubmitting.value) {
      return
    }

    const element = (event?.currentTarget ?? null) as HTMLFormElement | null

    formError.value = ''
    errors.value = validate(form)

    if (Object.keys(errors.value).length > 0) {
      await focusFirstInvalid(element)

      return
    }

    isSubmitting.value = true

    try {
      await action()
    }
    catch (error) {
      const { message, fields } = parseApiError(error)

      errors.value = fields as FormErrors<Form>

      if (Object.keys(fields).length === 0) {
        formError.value = message
      }

      await focusFirstInvalid(element)
    }
    finally {
      isSubmitting.value = false
    }
  }

  return { errors, formError, isSubmitting, submit }
}
