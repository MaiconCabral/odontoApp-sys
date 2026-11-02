<script setup lang="ts">
const { activeModal } = useActiveModal()
const auth = useAuthStore()

const isSidebarCollapsed = ref(false)
const isMobileMenuOpen = ref(false)

const route = useRoute()

// The menu counts the requests waiting for the reception on every page.
if (auth.user && auth.user.role !== 'professional') {
  useAppointmentRequests()
}

watch(() => route.path, () => {
  isMobileMenuOpen.value = false
})
</script>

<template>
  <div class="flex min-h-screen bg-canvas text-ink" @keydown.esc="isMobileMenuOpen = false">
    <button
      v-if="isMobileMenuOpen"
      type="button"
      class="fixed inset-0 z-[19] bg-[rgba(12,26,40,.3)] md:hidden"
      aria-label="Fechar menu"
      tabindex="-1"
      @click="isMobileMenuOpen = false"
    />

    <LayoutSidebar
      :collapsed="isSidebarCollapsed"
      :mobile-open="isMobileMenuOpen"
      @close="isMobileMenuOpen = false"
      @toggle-collapse="isSidebarCollapsed = !isSidebarCollapsed"
    />

    <div class="min-w-0 flex-1">
      <LayoutTopbar :menu-open="isMobileMenuOpen" @open-menu="isMobileMenuOpen = true" />
      <main class="mx-auto max-w-[1440px] px-[18px] pb-[45px] pt-[26px] md:px-[26px] md:pb-[55px] md:pt-[35px] lg:px-10">
        <slot />
      </main>
    </div>

    <Suspense v-if="activeModal === 'appointment'">
      <LazyAppointmentsNewModal />
    </Suspense>
    <Suspense v-else-if="activeModal === 'appointment-details'">
      <LazyAppointmentsDetailsModal />
    </Suspense>
    <Suspense v-else-if="activeModal === 'block'">
      <LazyAgendaBlockModal />
    </Suspense>
    <Suspense v-else-if="activeModal === 'patient'">
      <LazyPatientsNewModal />
    </Suspense>
    <AppToast />
  </div>
</template>
