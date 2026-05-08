<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import Dialog from '@/components/ui/dialog.vue'
import Sheet from '@/components/ui/sheet/index.vue'
import SheetContent from '@/components/ui/sheet/sheet.vue'
import PenjualanForm from '@/components/forms/PenjualanForm.vue'
import { Plus, Search, Eye, Pencil, Trash2, Filter, X, LayoutPanelLeft, Maximize2 } from 'lucide-vue-next'

const page = usePage()

interface Penjualan {
    id_penjualan: number
    no_nota: string
    tanggal_penjualan: string
    total: number
    diskon_total: number
    grand_total: number
    status_pembayaran: string
    member: { nama_member: string } | null
    karyawan: { nama: string } | null
}

type ViewMode = 'sheet' | 'modal'

const penjualans = computed(() => page.props.penjualans?.data || [])
const stats = computed(() => page.props.stats || {})
const filters = computed(() => page.props.filters || {})

const searchQuery = ref(filters.value.search || '')
const statusFilter = ref(filters.value.status || 'all')
const showForm = ref(false)
const viewMode = ref<ViewMode>('sheet')

try {
    const saved = localStorage.getItem('penjualan-view-mode') as ViewMode
    if (saved && ['sheet', 'modal'].includes(saved)) {
        viewMode.value = saved
    }
} catch {
    // Ignore localStorage errors
}

watch(viewMode, (mode) => {
    try {
        localStorage.setItem('penjualan-view-mode', mode)
    } catch {
        // Ignore localStorage errors
    }
})

function toggleViewMode(mode: ViewMode) {
    viewMode.value = mode
}

function openCreateForm() {
    showForm.value = true
}

function closeForm() {
    showForm.value = false
}

function formatCurrency(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

function applyFilters() {
    router.get('/app/admin/transactions/penjualan', {
        search: searchQuery.value,
        status: statusFilter.value,
        from: filters.value.from,
        to: filters.value.to,
    }, { preserveState: true })
}

function resetFilters() {
    searchQuery.value = ''
    statusFilter.value = 'all'
    router.get('/app/admin/transactions/penjualan', {}, { preserveState: true })
}

function deletePenjualan(id: number) {
    if (confirm('Are you sure you want to delete this transaction?')) {
        router.delete(`/app/admin/transactions/penjualan/${id}`)
    }
}

function onTransactionSaved() {
    showForm.value = false
    router.reload({ only: ['penjualans', 'stats'] })
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="Penjualan"
                description="Manage your sales transactions."
                :breadcrumbs="[
                    { label: 'Transactions', href: '/app/admin/transactions' },
                    { label: 'Penjualan' }
                ]"
            >
                <template #actions>
                    <Button @click="openCreateForm">
                        <Plus class="h-4 w-4 mr-2" />
                        New Transaction
                    </Button>
                </template>
            </PageHeader>
            
            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card class="p-4">
                    <p class="text-sm text-muted-foreground">Total Transactions</p>
                    <p class="text-2xl font-bold">{{ stats.total_count || 0 }}</p>
                </Card>
                <Card class="p-4">
                    <p class="text-sm text-muted-foreground">Lunas</p>
                    <p class="text-2xl font-bold text-green-600">{{ stats.lunas_count || 0 }}</p>
                </Card>
                <Card class="p-4">
                    <p class="text-sm text-muted-foreground">Belum Lunas</p>
                    <p class="text-2xl font-bold text-red-600">{{ stats.belum_lunas_count || 0 }}</p>
                </Card>
                <Card class="p-4">
                    <p class="text-sm text-muted-foreground">Total Revenue</p>
                    <p class="text-2xl font-bold">{{ formatCurrency(stats.total_revenue || 0) }}</p>
                </Card>
            </div>
            
            <!-- Filters -->
            <Card class="p-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="relative flex-1 min-w-[200px]">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search by no. nota or customer..."
                            class="pl-10"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    
                    <select
                        v-model="statusFilter"
                        class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="all">All Status</option>
                        <option value="lunas">Lunas</option>
                        <option value="belum_lunas">Belum Lunas</option>
                    </select>
                    
                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" @click="resetFilters">
                            <X class="h-4 w-4 mr-1" />
                            Reset
                        </Button>
                        <Button size="sm" @click="applyFilters">
                            <Filter class="h-4 w-4 mr-1" />
                            Filter
                        </Button>
                    </div>
                </div>
                
                <div v-if="filters.from && filters.to" class="mt-3 text-sm text-muted-foreground">
                    Filter: {{ formatDate(filters.from) }} - {{ formatDate(filters.to) }}
                </div>
            </Card>
            
            <!-- Table -->
            <Card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">No. Nota</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Tanggal</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Customer</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Total</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Diskon</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Grand Total</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="penjualan in penjualans"
                                :key="penjualan.id_penjualan"
                                class="hover:bg-muted/50 transition-colors"
                            >
                                <td class="px-4 py-3">
                                    <span class="font-medium">{{ penjualan.no_nota }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ formatDate(penjualan.tanggal_penjualan) }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ penjualan.member?.nama_member || '-' }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    {{ formatCurrency(penjualan.total) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-muted-foreground">
                                    {{ formatCurrency(penjualan.diskon_total || 0) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium">
                                    {{ formatCurrency(penjualan.grand_total) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Badge
                                        :variant="penjualan.status_pembayaran === 'lunas' ? 'success' : 'destructive'"
                                    >
                                        {{ penjualan.status_pembayaran === 'lunas' ? 'Lunas' : 'Belum Lunas' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <Link :href="`/app/admin/transactions/penjualan/${penjualan.id_penjualan}`">
                                            <Button variant="ghost" size="icon" class="h-8 w-8">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link :href="`/app/admin/transactions/penjualan/${penjualan.id_penjualan}/edit`">
                                            <Button variant="ghost" size="icon" class="h-8 w-8">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8 text-destructive hover:text-destructive"
                                            @click="deletePenjualan(penjualan.id_penjualan)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="penjualans.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">
                                    No transactions found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="border-t px-4 py-3 flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ page.props.penjualans?.from || 0 }} to {{ page.props.penjualans?.to || 0 }}
                        of {{ page.props.penjualans?.total || 0 }} results
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="page.props.penjualans?.prev_page_url"
                            :href="page.props.penjualans.prev_page_url"
                        >
                            <Button variant="outline" size="sm">Previous</Button>
                        </Link>
                        <Link
                            v-if="page.props.penjualans?.next_page_url"
                            :href="page.props.penjualans.next_page_url"
                        >
                            <Button variant="outline" size="sm">Next</Button>
                        </Link>
                    </div>
                </div>
            </Card>
        </div>
        
        <!-- Dynamic Form Wrapper (Sheet or Dialog) -->
        <component :is="viewMode === 'sheet' ? Sheet : 'div'" v-if="showForm || viewMode === 'sheet'">
            <component
                :is="viewMode === 'sheet' ? SheetContent : Dialog"
                :open="showForm"
                @update:open="showForm = $event"
                :class="viewMode === 'sheet' ? 'w-[600px] sm:max-w-[600px]' : 'max-w-2xl max-h-[90vh]'"
            >
                <div :class="viewMode === 'sheet' ? 'space-y-6 h-full flex flex-col' : 'space-y-6 flex flex-col max-h-[calc(90vh-3rem)]'">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold">New Transaction</h2>
                            <p class="text-sm text-muted-foreground">Create a new sales transaction</p>
                        </div>
                        <div class="flex items-center gap-2" :class="viewMode === 'modal' ? '-mr-2 -mt-2' : ''">
                            <div class="flex items-center gap-1 rounded-lg border bg-muted p-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7"
                                    :class="viewMode === 'sheet' ? 'bg-background shadow-sm' : 'hover:bg-transparent'"
                                    @click="toggleViewMode('sheet')"
                                    title="Slide-over Mode"
                                >
                                    <LayoutPanelLeft class="h-3.5 w-3.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7"
                                    :class="viewMode === 'modal' ? 'bg-background shadow-sm' : 'hover:bg-transparent'"
                                    @click="toggleViewMode('modal')"
                                    title="Popup Mode"
                                >
                                    <Maximize2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                            <Button variant="ghost" size="icon" class="shrink-0" @click="closeForm" title="Close">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div class="overflow-y-auto pr-2 flex-1" :class="viewMode === 'sheet' ? 'flex-1' : ''">
                        <PenjualanForm @saved="onTransactionSaved" />
                    </div>
                </div>
            </component>
        </component>
    </AppLayout>
</template>
