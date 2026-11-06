import type { Paged } from '~/services/clinical'

// List loaded one page at a time: the first page on start, the next ones on "Carregar mais".
export function usePagedList<T>(fetchPage: (page: number) => Promise<Paged<T>>) {
  const items = ref<T[]>([]) as Ref<T[]>
  const page = ref(0)
  const lastPage = ref(1)
  const isLoading = ref(false)
  const loadFailed = ref(false)

  // Loads one page; the first one replaces the list, the next ones are appended.
  async function fetchAt(target: number) {
    isLoading.value = true
    loadFailed.value = false

    try {
      const result = await fetchPage(target)

      items.value = target === 1 ? result.items : [...items.value, ...result.items]
      page.value = result.page
      lastPage.value = result.lastPage
    }
    catch {
      loadFailed.value = true
    }
    finally {
      isLoading.value = false
    }
  }

  return {
    items,
    page,
    lastPage,
    isLoading,
    loadFailed,
    hasMore: computed(() => page.value < lastPage.value),
    // Starts over from the first page.
    reload: () => fetchAt(1),
    // Appends the next page.
    loadMore: () => fetchAt(page.value + 1),
  }
}
