<script setup lang="ts">
import Layout from '@/layouts/Default.vue'
import { Head, router } from '@inertiajs/vue3'
import { h, ref, resolveComponent, reactive } from 'vue'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { TableColumn } from '@nuxt/ui'

defineOptions({ layout: Layout })

const props = defineProps<{
  records: Array<{
    id: number
    date: string
    income: string
    expense: string
    net: string
  }>
  filters: { date?: string }
}>()

// Komponen UI yang digunakan
const UButton = resolveComponent('UButton')
const UDropdownMenu = resolveComponent('UDropdownMenu')
const UAlert = resolveComponent('UAlert')
const UInput = resolveComponent('UInput')
const UPagination = resolveComponent('UPagination')
const UTable = resolveComponent('UTable')
const UModal = resolveComponent('UModal')

// State modal edit
const selectedRecord = ref<any>(null)
const showEditModal = ref(false)

// Filter tanggal
const filters = reactive({
  date: props.filters.date || '',
})

// State alert
const showAlert = ref(false)
const alertMessage = ref('')
const alertColor = ref<'success' | 'error'>('success')

function showAlertMessage(message: string, color: 'success' | 'error' = 'success') {
  alertMessage.value = message
  alertColor.value = color
  showAlert.value = true
  setTimeout(() => (showAlert.value = false), 3000)
}

// Modal hapus
const deleteModal = ref<{ open: boolean; recordId?: number }>({ open: false })

function openDeleteModal(id: number) {
  deleteModal.value = { open: true, recordId: id }
}

// Konfirmasi hapus data
async function confirmDelete() {
  if (!deleteModal.value.recordId) return
  await router.delete(`/finance-records/${deleteModal.value.recordId}`, {
    onSuccess: () => {
      deleteModal.value.open = false
      showAlertMessage('Catatan keuangan berhasil dihapus.', 'success')
    },
    onError: () => {
      deleteModal.value.open = false
      showAlertMessage('Gagal menghapus catatan keuangan.', 'error')
    },
  })
}

// Filter berdasarkan tanggal
function searchFinance() {
  router.get('/finance-records', { date: filters.date }, { preserveState: true, replace: true })
}

// Kolom tabel
const columns: TableColumn[] = [
  {
    id: 'no',
    header: 'No',
    cell: ({ row, table }) => {
      const pageIndex = table.getState().pagination.pageIndex
      const pageSize = table.getState().pagination.pageSize
      return h('span', {}, pageIndex * pageSize + row.index + 1)
    },
  },
  {
    accessorKey: 'date',
    header: 'Tanggal',
    cell: ({ row }) => h('span', {}, row.original.date),
  },
  {
    accessorKey: 'income',
    header: 'Uang Masuk (Rp)',
    cell: ({ row }) => h('span', {}, row.original.income),
  },
  {
    accessorKey: 'expense',
    header: 'Uang Keluar (Rp)',
    cell: ({ row }) => h('span', {}, row.original.expense),
  },
  {
    accessorKey: 'net',
    header: 'Selisih (Rp)',
    cell: ({ row }) =>
      h(
        'span',
        { class: row.original.net.startsWith('-') ? 'text-red-600' : 'text-green-600' },
        row.original.net
      ),
  },
  {
    id: 'actions',
    header: 'Aksi',
    cell: ({ row }) =>
      h(
        'div',
        { class: 'text-right' },
        h(
          UDropdownMenu,
          {
            content: { align: 'end' },
            items: [
              {
                label: 'Edit',
                onSelect: () => {
                  selectedRecord.value = row.original
                  showEditModal.value = true
                },
              },
              { type: 'separator' },
              {
                label: 'Hapus',
                color: 'error',
                onSelect: () => openDeleteModal(row.original.id),
              },
            ],
          },
          () =>
            h(UButton, {
              icon: 'i-lucide-ellipsis-vertical',
              color: 'neutral',
              variant: 'ghost',
            })
        )
      ),
  },
]

const pagination = ref({ pageIndex: 0, pageSize: 10 })
</script>

<template>
  <Head title="Catatan Keuangan" />

  <UDashboardPanel id="finance-records">
    <template #header>
      <UDashboardNavbar title="Catatan Keuangan">
        <template #right>
          <FinanceAddModal />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <!-- Filter tanggal -->
      <div class="flex gap-2 mb-4 items-center">
        <UInput
          v-model="filters.date"
          type="date"
          class="max-w-xs"
          icon="i-lucide-calendar"
          placeholder="Pilih tanggal..."
          @change="searchFinance"
        />
        <UButton label="Cari" icon="i-lucide-search" @click="searchFinance" />
      </div>

      <!-- Tabel catatan keuangan -->
      <UTable
        :data="records"
        :columns="columns"
        v-model:pagination="pagination"
        :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"
        :ui="{
          base: 'table-fixed border-separate border-spacing-0 w-full',
          thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
          th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
          td: 'border-b border-default py-2 px-3',
        }"
      />

      <!-- Footer tabel -->
      <div
        class="mt-4 flex items-center justify-between text-sm text-muted border-t border-default pt-4"
      >
        <div>Total Catatan: <strong>{{ records.length }}</strong></div>
        <UPagination
          :default-page="(pagination.pageIndex || 0) + 1"
          :items-per-page="pagination.pageSize"
          :total="records.length"
          @update:page="(p: number) => (pagination.pageIndex = p - 1)"
        />
      </div>

      <!-- Modal Edit -->
      <FinanceEditModal
        v-if="selectedRecord"
        :record="selectedRecord"
        v-model:open="showEditModal"
      />

      <!-- Modal Hapus -->
      <UModal
        v-model:open="deleteModal.open"
        title="Hapus Catatan Keuangan?"
        description="Tindakan ini tidak dapat dibatalkan."
      >
        <template #body>
          <div class="flex justify-end gap-2">
            <UButton
              label="Batal"
              color="neutral"
              variant="subtle"
              @click="deleteModal.open = false"
            />
            <UButton
              label="Hapus"
              color="error"
              variant="solid"
              @click="confirmDelete"
            />
          </div>
        </template>
      </UModal>

      <!-- Alert notifikasi -->
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
    </template>
  </UDashboardPanel>
</template>
