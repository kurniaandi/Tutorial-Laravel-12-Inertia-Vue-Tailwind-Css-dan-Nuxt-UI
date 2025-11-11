<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import type { FormSubmitEvent } from '@nuxt/ui'
import * as z from 'zod'

// Props Finance Record untuk edit
const props = defineProps<{
  record: {
    id: number
    date: string
    income: number | string
    expense: number | string
  }
  modelValue?: boolean
}>()

const emit = defineEmits(['update:modelValue', 'updated'])
const open = defineModel<boolean>('open', { default: true })

// Schema validasi
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

// State form
const state = reactive({
  date: '',
  income: 0,
  expense: 0,
})

// Normalisasi data dari props ke state
watch(
  () => props.record,
  (record) => {
    if (record) {
      state.date = record.date
      console.log('Date from backend:', record.date) 

      state.income = Number(String(record.income).replace(/,/g, '')) || 0
      state.expense = Number(String(record.expense).replace(/,/g, '')) || 0
    }
  },
  { immediate: true }
)


// State alert
const showAlert = ref(false)
const alertMessage = ref('')
const alertColor = ref<'success' | 'error'>('success')

// Submit handler
async function onSubmit(event: FormSubmitEvent<typeof schema>) {
  const formData = new FormData()
  formData.append('date', state.date)
  formData.append('income', String(state.income))
  formData.append('expense', String(state.expense))
  formData.append('_method', 'PUT')

  router.post(`/finance-records/${props.record.id}`, formData, {
    forceFormData: true,
    onSuccess: () => {
      alertMessage.value = 'Catatan keuangan berhasil diperbarui.'
      alertColor.value = 'success'
      showAlert.value = true
      emit('updated')

      setTimeout(() => (open.value = false), 500)
      setTimeout(() => (showAlert.value = false), 3000)
    },
    onError: (errors: any) => {
      const allErrors =
        Object.values(errors || {}).flat().join(' | ') ||
        'Gagal memperbarui catatan keuangan.'
      alertMessage.value = allErrors
      alertColor.value = 'error'
      showAlert.value = true
      setTimeout(() => (showAlert.value = false), 3000)
    },
  })
}
</script>

<template>
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

  <!-- Modal Edit Finance Record -->
  <UModal
    v-model:open="open"
    title="Edit Catatan Keuangan"
    description="Perbarui tanggal, jumlah uang masuk, dan uang keluar."
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
            step="0.01"
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
            step="0.01"
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
          <UButton label="Perbarui" color="primary" variant="solid" type="submit" />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
