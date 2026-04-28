<script setup lang="ts">
import AppLayout from '@/components/layout/AppLayout.vue'
import { computed, ref, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Plus, Search, Eye, Pencil, Trash2, CheckCircle2, ClipboardList } from 'lucide-vue-next'

import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import DataTable from '@/components/tables/DataTable.vue'
import Dialog from '@/components/ui/dialog.vue'

interface Gudang {
    id: number
    nama_gudang: string
}

interface User {
    id: number
    name: string
}

interface Adjustment {
    id: number
    kode: string
    tanggal: string
    status: 'draft' | 'posted'
    gudang: Gudang | null
    user: User | null
    catatan: string | null
    created_at: string
}

interface PaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

interface PageProps {
    adjustments: { data: Adjustment[] } & PaginationMeta
    filters: { search?: string; status?: string }
}

const page = usePage<PageProps>()

const adjustments = computed(() => page.props.adjustments.data)
const paginationMeta = computed(() => ({
    current_page: page.props.adjustments.current_page,
    last_page: page.props.adjustments.last_page,
    per_page: page.props.adjustments.per_page,
    total: page.props.adjustments.total,
}))

// Filters
const searchQuery = ref(page.props.filters.search || '')
const statusFilter = ref(page.props.filters.status || '')
let searchTimeout: ReturnType<typeof setTimeout> | null = null

function applyFilters() {
    router.get(
        '/app/admin/inventory/stock-adjustment',
        {
            search: searchQuery.value || undefined,
            status: statusFilter.value || undefined,
        },
        { preserveState: true, replace: true }
    )
}

watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilters, 300)
})

watch(statusFilter, applyFilters)

const isLoading = ref(false)
const showDeleteModal = ref(false)
const selectedAdjustment = ref<Adjustment | null>(null)

const columns = [
    { key: 'kode', label: 'Code', sortable: true },
    { key: 'tanggal', label: 'Date', sortable: true },
    { key: 'gudang', label: 'Warehouse', sortable: false },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'catatan', label: 'Note', sortable: false },
]

function handleSort() {}

function handlePageChange(pageNum: number) {
    isLoading.value = true
    router.get(
        '/app/admin/inventory/stock-adjustment',
        {
            page: pageNum,
            search: searchQuery.value || undefined,
            status: statusFilter.value || undefined,
        },
        { preserveState: true, onFinish: () => { isLoading.value = false } }
    )
}

function openDeleteModal(row: Adjustment) {
    selectedAdjustment.value = row
    showDeleteModal.value = true
}

function confirmDelete() {
    if (!selectedAdjustment.value) return
    router.delete(`/app/admin/inventory/stock-adjustment/${selectedAdjustment.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false
            selectedAdjustment.value = null
        },
    })
}

function postAdjustment(row: Adjustment) {
    if (!confirm(`Post adjustment ${row.kode}? This cannot be undone.`)) return
    router.post(`/app/admin/inventory/stock-adjustment/${row.id}/post`)
}

function statusVariant(status: string): 'success' | 'warning' | 'secondary' {
    if (status === 'posted') return 'success'
    return 'warning'
}

function formatDate(dateStr: string): string {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="Stock Adjustment"
                description="Manage inventory adjustments to correct stock discrepancies."
                :breadcrumbs="[
                    { label: 'Inventory', href: '/app/admin/inventory/stock-adjustment' },
                    { label: 'Stock Adjustment' },
                ]"
            >
                <template #actions>
                    <Button as="a" href="/app/admin/inventory/stock-adjustment/create" class="gap-2">
                        <Plus class="h-4 w-4" />
                        <span class="hidden sm:inline">New Adjustment</span>
                        <span class="sm:hidden">New</span>
                    </Button>
                </template>
            </PageHeader>

            <Card class="p-6">
                <!-- Filters -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-6">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search by code or note..."
                            class="pl-10"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        class="h-9 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                    </select>
                </div>

                <DataTable
                    :data="adjustments"
                    :columns="columns"
                    :pagination="paginationMeta"
                    :loading="isLoading"
                    @sort="handleSort"
                    @page-change="handlePageChange"
                >
                    <template #cell:kode="{ row }">
                        <div class="flex items-center gap-2">
                            <ClipboardList class="h-4 w-4 text-muted-foreground shrink-0" />
                            <span class="font-mono font-medium text-sm">{{ row.kode }}</span>
                        </div>
                    </template>

                    <template #cell:tanggal="{ row }">
                        {{ formatDate(row.tanggal) }}
                    </template>

                    <template #cell:gudang="{ row }">
                        <span v-if="row.gudang">{{ row.gudang.nama_gudang }}</span>
                        <span v-else class="text-muted-foreground">-</span>
                    </template>

                    <template #cell:status="{ row }">
                        <Badge :variant="statusVariant(row.status)" class="capitalize">
                            {{ row.status }}
                        </Badge>
                    </template>

                    <template #cell:catatan="{ row }">
                        <span v-if="row.catatan" class="text-sm text-muted-foreground truncate max-w-[200px] block">
                            {{ row.catatan }}
                        </span>
                        <span v-else class="text-muted-foreground">-</span>
                    </template>

                    <template #actions="{ row }">
                        <div class="flex items-center gap-1">
                            <Button
                                variant="ghost"
                                size="sm"
                                title="View"
                                @click.stop="router.visit(`/app/admin/inventory/stock-adjustment/${row.id}`)"
                            >
                                <Eye class="h-4 w-4" />
                            </Button>
                            <Button
                                v-if="row.status === 'draft'"
                                variant="ghost"
                                size="sm"
                                title="Edit"
                                @click.stop="router.visit(`/app/admin/inventory/stock-adjustment/${row.id}/edit`)"
                            >
                                <Pencil class="h-4 w-4" />
                            </Button>
                            <Button
                                v-if="row.status === 'draft'"
                                variant="ghost"
                                size="sm"
                                title="Post"
                                class="text-green-600 hover:text-green-700"
                                @click.stop="postAdjustment(row)"
                            >
                                <CheckCircle2 class="h-4 w-4" />
                            </Button>
                            <Button
                                v-if="row.status === 'draft'"
                                variant="ghost"
                                size="sm"
                                title="Delete"
                                @click.stop="openDeleteModal(row)"
                            >
                                <Trash2 class="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                    </template>
                </DataTable>
            </Card>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog
            :open="showDeleteModal"
            @update:open="showDeleteModal = $event"
            class="max-w-md"
        >
            <div class="space-y-4">
                <div>
                    <h2 class="text-lg font-semibold">Delete Adjustment?</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Are you sure you want to delete
                        <strong>{{ selectedAdjustment?.kode }}</strong>?
                        This action cannot be undone.
                    </p>
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="showDeleteModal = false">Cancel</Button>
                    <Button variant="destructive" @click="confirmDelete">Delete</Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
