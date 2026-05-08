<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import Slideover from '@/components/ui/slideover.vue'
import PembelianForm from '@/components/forms/PembelianForm.vue'
import { Plus, Search, Eye, Pencil, Trash2, Filter, X } from 'lucide-vue-next'

const page = usePage()

interface Pembelian {
    id_pembelian: number
    no_po: string
    nota_supplier: string
    tanggal: string
    total_amount: number
    jenis_pembayaran: string
    supplier: { nama_supplier: string } | null
    karyawan: { nama: string } | null
}

const pembelians = computed(() => page.props.pembelians?.data || [])
const stats = computed(() => page.props.stats || {})
const filters = computed(() => page.props.filters || {})

const searchQuery = ref(filters.value.search || '')
const statusFilter = ref(filters.value.status || 'all')
const showCreateSlideover = ref(false)

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
    router.get('/app/admin/transactions/pembelian', {
        search: searchQuery.value,
        status: statusFilter.value,
        from: filters.value.from,
        to: filters.value.to,
    }, { preserveState: true })
}

function resetFilters() {
    searchQuery.value = ''
    statusFilter.value = 'all'
    router.get('/app/admin/transactions/pembelian', {}, { preserveState: true })
}

function deletePembelian(id: number) {
    if (confirm('Are you sure you want to delete this purchase?')) {
        router.delete(`/app/admin/transactions/pembelian/${id}`)
    }
}

function onPurchaseSaved() {
    showCreateSlideover.value = false
    router.reload({ only: ['pembelians', 'stats'] })
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="Pembelian"
                description="Manage your purchase transactions."
                :breadcrumbs="[
                    { label: 'Transactions', href: '/app/admin/transactions' },
                    { label: 'Pembelian' }
                ]"
            >
                <template #actions>
                    <Button @click="showCreateSlideover = true">
                        <Plus class="h-4 w-4 mr-2" />
                        New Purchase
                    </Button>
                </template>
            </PageHeader>
            
            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card class="p-4">
                    <p class="text-sm text-muted-foreground">Total Purchases</p>
                    <p class="text-2xl font-bold">{{ stats.total_count || 0 }}</p>
                </Card>
                <Card class="p-4">
                    <p class="text-sm text-muted-foreground">Lunas</p>
                    <p class="text-2xl font-bold text-green-600">{{ stats.lunas_count || 0 }}</p>
                </Card>
                <Card class="p-4">
                    <p class="text-sm text-muted-foreground">Tempo</p>
                    <p class="text-2xl font-bold text-red-600">{{ stats.tempo_count || 0 }}</p>
                </Card>
                <Card class="p-4">
                    <p class="text-sm text-muted-foreground">Total Nilai</p>
                    <p class="text-2xl font-bold">{{ formatCurrency(stats.total_nilai || 0) }}</p>
                </Card>
            </div>
            
            <!-- Filters -->
            <Card class="p-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="relative flex-1 min-w-[200px]">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search by no. PO or supplier..."
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
                        <option value="tempo">Tempo</option>
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
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">No. PO</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Tanggal</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Supplier</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Nota Supplier</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Total</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-muted-foreground">Payment</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="pembelian in pembelians"
                                :key="pembelian.id_pembelian"
                                class="hover:bg-muted/50 transition-colors"
                            >
                                <td class="px-4 py-3">
                                    <span class="font-medium">{{ pembelian.no_po }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ formatDate(pembelian.tanggal) }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ pembelian.supplier?.nama_supplier || '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">
                                    {{ pembelian.nota_supplier || '-' }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium">
                                    {{ formatCurrency(pembelian.total_amount) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Badge
                                        :variant="pembelian.jenis_pembayaran === 'lunas' ? 'success' : 'secondary'"
                                    >
                                        {{ pembelian.jenis_pembayaran === 'lunas' ? 'Lunas' : 'Tempo' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <Link :href="`/app/admin/transactions/pembelian/${pembelian.id_pembelian}`">
                                            <Button variant="ghost" size="icon" class="h-8 w-8">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link :href="`/app/admin/transactions/pembelian/${pembelian.id_pembelian}/edit`">
                                            <Button variant="ghost" size="icon" class="h-8 w-8">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8 text-destructive hover:text-destructive"
                                            @click="deletePembelian(pembelian.id_pembelian)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="pembelians.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">
                                    No purchases found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="border-t px-4 py-3 flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ page.props.pembelians?.from || 0 }} to {{ page.props.pembelians?.to || 0 }}
                        of {{ page.props.pembelians?.total || 0 }} results
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="page.props.pembelians?.prev_page_url"
                            :href="page.props.pembelians.prev_page_url"
                        >
                            <Button variant="outline" size="sm">Previous</Button>
                        </Link>
                        <Link
                            v-if="page.props.pembelians?.next_page_url"
                            :href="page.props.pembelians.next_page_url"
                        >
                            <Button variant="outline" size="sm">Next</Button>
                        </Link>
                    </div>
                </div>
            </Card>
        </div>
        
        <Slideover
            v-model:open="showCreateSlideover"
            title="New Purchase"
            size="xl"
        >
            <PembelianForm @saved="onPurchaseSaved" />
        </Slideover>
    </AppLayout>
</template>
