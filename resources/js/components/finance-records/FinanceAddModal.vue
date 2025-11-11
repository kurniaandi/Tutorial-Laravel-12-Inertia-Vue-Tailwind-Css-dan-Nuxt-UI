<script setup lang="ts">
import { reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import type { FormSubmitEvent } from '@nuxt/ui'
import * as z from 'zod'

// Schema validasi Finance Record
const schema = z.object({
  date: z.string().min(1, 'Tanggal harus diisi'),
  income: z
    .number({ invalid_type_error: 'Uang masuk harus berupa angka' })
    .nonnegative('Uang masuk tidak boleh negatif'),
  expense: z
    .number({ invalid_type_error: 'Uang keluar harus berupa angka' })
    .nonnegative('Uang keluar tidak boleh negatif'),
})
type Schema = z.output<typeof schema>

// State modal
const open = ref(false)

// State form
const state = reactive<Partial<Schema>>({
  date: '',
  income: 0,
  expense: 0,
})

// State alert
const showAlert = ref(false)
const alertMessage = ref('')
const alertColor = ref<'success' | 'error'>('success')

// Submit handler
async function onSubmit(event: FormSubmitEvent<Schema>) {
  router.post('/finance-records', event.data, {
    onSuccess: () => {
      alertMessage.value = 'Catatan keuangan berhasil ditambahkan.'
      alertColor.value = 'success'
      showAlert.value = true

      // Reset form dan tutup modal
      open.value = false
      state.date = ''
      state.income = 0
      state.expense = 0

      // Sembunyikan alert otomatis
      setTimeout(() => (showAlert.value = false), 3000)
    },
    onError: (errors: any) => {
      const allErrors =
        Object.values(errors || {}).flat().join(' | ') ||
        'Gagal menambahkan catatan keuangan.'
      alertMessage.value = allErrors
      alertColor.value = 'error'
      showAlert.value = true
      setTimeout(() => (showAlert.value = false), 3000)
    },
  })
}
</script>

<template>
  <!-- Tombol utama -->
  <UButton
    label="Tambah Catatan Keuangan"
    icon="i-lucide-plus"
    color="primary"
    @click="open = true"
  />

  <!-- Alert pojok kanan bawah -->
  <div class="fixed bottom-4 right-4 z-50">
    <UAlert
      v-if="showAlert"
      :title="alertMessage"
      :color="alertColor"
      variant="subtle"
      :icon="
        alertColor === 'success'
          ? 'i-lucide-check-circle'
          : 'i-lucide-alert-circle'
      "
      close
      @update:open="showAlert = false"
    />
  </div>

  <!-- Modal tambah catatan keuangan -->
  <UModal
    v-model:open="open"
    title="Tambah Catatan Keuangan"
    description="Isi tanggal, jumlah uang masuk, dan uang keluar."
    class="max-w-lg"
  >
    <template #body>
      <UForm :schema="schema" :state="state" class="space-y-5" @submit="onSubmit">
        <!-- Tanggal -->
        <UFormField name="date" label="Tanggal" required class="w-full">
          <UInput v-model="state.date" type="date" class="w-full" />
        </UFormField>

        <!-- Uang Masuk -->
        <UFormField name="income" label="Uang Masuk (Rp)" required class="w-full">
          <UInput
            v-model.number="state.income"
            type="number"
            min="0"
            placeholder="Masukkan jumlah uang masuk"
            class="w-full"
          />
        </UFormField>

        <!-- Uang Keluar -->
        <UFormField name="expense" label="Uang Keluar (Rp)" required class="w-full">
          <UInput
            v-model.number="state.expense"
            type="number"
            min="0"
            placeholder="Masukkan jumlah uang keluar"
            class="w-full"
          />
        </UFormField>

        <USeparator />

        <!-- Tombol aksi -->
        <div class="flex justify-end gap-3 mt-4">
          <UButton
            label="Batal"
            color="neutral"
            variant="subtle"
            @click="open = false"
          />
          <UButton label="Simpan" color="primary" variant="solid" type="submit" />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
