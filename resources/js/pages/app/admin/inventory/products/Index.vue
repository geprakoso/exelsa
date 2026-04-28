<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import {
    Package, DollarSign, AlertTriangle, XCircle,
    Search, Plus, Download, Upload, Pencil, MoreHorizontal,
    ArrowUpRight, ArrowDownRight, Minus, Image,
    ChevronLeft, ChevronRight,
} from 'lucide-vue-next'

// ── Types ─────────────────────────────────────────────────────────────────
interface Product {
    id: number
    nama_produk: string
    sku: string
    image_url: string | null
    brand: { id: number; nama_brand: string } | null
    kategori: { id: number; nama_kategori: string } | null
    stok_on_hand: number
    avg_hpp: number
    harga_jual_display: number
    recent_in: number
    recent_out: number
}

interface PaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

interface Stats {
    total_products: number
    total_stock_value: number
    low_stock_count: number
    out_of_stock_count: number
}

interface TabCounts {
    all: number
    active: number
    low_stock: number
    out_of_stock: number
    discontinued: number
}

interface PageProps {
    products: { data: Product[] } & PaginationMeta
    kategoris: { id: number; nama_kategori: string }[]
    gudangs: { id: number; nama_gudang: string }[]
    filters: Record<string, string>
    stats: Stats
    tab_counts: TabCounts
    low_stock_threshold: number
}

// ── Page props ────────────────────────────────────────────────────────────
const page = usePage<PageProps>()

const products = computed(() => page.props.products.data)
const pagination = computed(() => page.props.products)
const stats      = computed(() => page.props.stats)
const tabCounts  = computed(() => page.props.tab_counts)
const kategoris  = computed(() => page.props.kategoris || [])
const threshold  = computed(() => page.props.low_stock_threshold)

// ── Filters ───────────────────────────────────────────────────────────────
const searchQuery  = ref(page.props.filters.search   || '')
const kategoriId   = ref(page.props.filters.kategori_id || '')
const activeTab    = ref(page.props.filters.tab      || 'all')
const perPage      = ref(page.props.filters.per_page || '10')

let debounce: ReturnType<typeof setTimeout> | null = null

function navigate(extra: Record<string, string | number | undefined> = {}) {
    router.get('/app/admin/inventory/products', {
        search:      searchQuery.value  || undefined,
        kategori_id: kategoriId.value   || undefined,
        tab:         activeTab.value !== 'all' ? activeTab.value : undefined,
        per_page:    perPage.value !== '10'    ? perPage.value   : undefined,
        ...extra,
    }, { preserveState: true, replace: true })
}

watch(searchQuery, (v) => {
    if (debounce) clearTimeout(debounce)
    debounce = setTimeout(() => navigate({ page: 1 }), 300)
})
watch(kategoriId, () => navigate({ page: 1 }))
watch(activeTab,  () => navigate({ page: 1 }))
watch(perPage,    () => navigate({ page: 1 }))

function goToPage(p: number) { navigate({ page: p }) }

// ── Helpers ───────────────────────────────────────────────────────────────
const TABS = [
    { key: 'all',          label: 'All Products',   count: computed(() => tabCounts.value.all) },
    { key: 'active',       label: 'Active',         count: computed(() => tabCounts.value.active) },
    { key: 'low_stock',    label: 'Low Stock',      count: computed(() => tabCounts.value.low_stock) },
    { key: 'out_of_stock', label: 'Out of Stock',   count: computed(() => tabCounts.value.out_of_stock) },
    { key: 'discontinued', label: 'Discontinued',   count: computed(() => tabCounts.value.discontinued) },
]

function formatCurrency(n: number | string): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(n))
}

function formatNumber(n: number | string): string {
    return new Intl.NumberFormat('id-ID').format(Number(n))
}

function getStockStatus(stok: number): 'active' | 'low_stock' | 'out_of_stock' {
    if (stok === 0 || stok <= 0) return 'out_of_stock'
    if (stok <= threshold.value)  return 'low_stock'
    return 'active'
}

type StatusVariant = 'success' | 'warning' | 'destructive'
function statusVariant(stok: number): StatusVariant {
    const s = getStockStatus(stok)
    if (s === 'active')       return 'success'
    if (s === 'low_stock')    return 'warning'
    return 'destructive'
}

function statusLabel(stok: number): string {
    const s = getStockStatus(stok)
    if (s === 'active')      return 'Active'
    if (s === 'low_stock')   return 'Low Stock'
    return 'Out of Stock'
}

const CATEGORY_PALETTE: Record<string, string> = {}
const COLORS = [
    'bg-blue-100 text-blue-800',
    'bg-green-100 text-green-800',
    'bg-orange-100 text-orange-800',
    'bg-yellow-100 text-yellow-800',
    'bg-purple-100 text-purple-800',
    'bg-pink-100 text-pink-800',
    'bg-cyan-100 text-cyan-800',
    'bg-red-100 text-red-800',
]
let colorIdx = 0

function categoryColor(name: string | undefined): string {
    if (!name) return 'bg-secondary text-secondary-foreground'
    if (!CATEGORY_PALETTE[name]) {
        CATEGORY_PALETTE[name] = COLORS[colorIdx % COLORS.length]
        colorIdx++
    }
    return CATEGORY_PALETTE[name]
}

// Net movement = recent_in - recent_out (last 30 days)
function netMovement(p: Product): number {
    return Number(p.recent_in) - Number(p.recent_out)
}

// Selectable rows
const selectedIds = ref<Set<number>>(new Set())
const allSelected = computed(() =>
    products.value.length > 0 && products.value.every(p => selectedIds.value.has(p.id))
)
function toggleAll() {
    if (allSelected.value) {
        products.value.forEach(p => selectedIds.value.delete(p.id))
    } else {
        products.value.forEach(p => selectedIds.value.add(p.id))
    }
    // trigger reactivity
    selectedIds.value = new Set(selectedIds.value)
}
function toggleRow(id: number) {
    if (selectedIds.value.has(id)) {
        selectedIds.value.delete(id)
    } else {
        selectedIds.value.add(id)
    }
    selectedIds.value = new Set(selectedIds.value)
}

// Pagination pages to show
const pageRange = computed(() => {
    const total = pagination.value.last_page
    const cur   = pagination.value.current_page
    const pages: (number | '...')[] = []
    if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i)
    } else {
        pages.push(1)
        if (cur > 3) pages.push('...')
        for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i)
        if (cur < total - 2) pages.push('...')
        pages.push(total)
    }
    return pages
})
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">

            <!-- ── Page Header ─────────────────────────────────────────── -->
            <PageHeader
                title="Inventory Products"
                description="Manage your inventory items, stock levels, and product information."
                :breadcrumbs="[
                    { label: 'Inventory', href: '/app/admin/inventory/products' },
                    { label: 'Products' },
                ]"
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" class="gap-2" disabled>
                            <Upload class="h-4 w-4" />
                            Import
                        </Button>
                        <Button variant="outline" size="sm" class="gap-2" disabled>
                            <Download class="h-4 w-4" />
                            Export
                        </Button>
                        <Button
                            size="sm"
                            class="gap-2"
                            @click="router.visit('/app/admin/master-data/produk')"
                        >
                            <Plus class="h-4 w-4" />
                            Add Product
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <!-- ── Stats Cards ─────────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Products -->
                <Card class="p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Total Products</p>
                        <p class="text-2xl font-bold">{{ formatNumber(stats.total_products) }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Active products</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                        <Package class="h-6 w-6 text-blue-600" />
                    </div>
                </Card>

                <!-- Total Stock Value -->
                <Card class="p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Total Stock Value</p>
                        <p class="text-2xl font-bold">{{ formatCurrency(stats.total_stock_value) }}</p>
                        <p class="text-xs text-muted-foreground mt-1">At cost</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                        <DollarSign class="h-6 w-6 text-green-600" />
                    </div>
                </Card>

                <!-- Low Stock -->
                <Card class="p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Low Stock Items</p>
                        <p class="text-2xl font-bold">{{ formatNumber(stats.low_stock_count) }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Require attention</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center shrink-0">
                        <AlertTriangle class="h-6 w-6 text-yellow-600" />
                    </div>
                </Card>

                <!-- Out of Stock -->
                <Card class="p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Out of Stock Items</p>
                        <p class="text-2xl font-bold">{{ formatNumber(stats.out_of_stock_count) }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Require restocking</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                        <XCircle class="h-6 w-6 text-red-500" />
                    </div>
                </Card>
            </div>

            <!-- ── Main Table Card ─────────────────────────────────────── -->
            <Card>
                <!-- Tabs -->
                <div class="border-b px-6">
                    <nav class="flex gap-0 -mb-px">
                        <button
                            v-for="tab in TABS"
                            :key="tab.key"
                            class="flex items-center gap-1.5 px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                            :class="activeTab === tab.key
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted-foreground/40'"
                            @click="activeTab = tab.key"
                        >
                            {{ tab.label }}
                            <span
                                class="inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold min-w-[18px]"
                                :class="activeTab === tab.key
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground'"
                            >
                                {{ tab.count.value }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Toolbar -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 px-6 py-4 border-b">
                    <!-- Search -->
                    <div class="relative flex-1 max-w-xs">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search by code, name..."
                            class="pl-9 h-9"
                        />
                    </div>

                    <!-- Category filter -->
                    <div class="flex flex-col gap-0.5">
                        <label class="text-[10px] font-medium text-muted-foreground uppercase tracking-wide">Category</label>
                        <select
                            v-model="kategoriId"
                            class="h-9 rounded-md border border-input bg-background px-3 py-2 text-sm min-w-[140px]"
                        >
                            <option value="">All Categories</option>
                            <option v-for="k in kategoris" :key="k.id" :value="String(k.id)">
                                {{ k.nama_kategori }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/40 border-b">
                            <tr>
                                <th class="w-10 px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-input"
                                        :checked="allSelected"
                                        @change="toggleAll"
                                    />
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-muted-foreground text-xs uppercase tracking-wide">
                                    Product Code
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-muted-foreground text-xs uppercase tracking-wide">
                                    Product Name
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-muted-foreground text-xs uppercase tracking-wide">
                                    Category
                                </th>
                                <th class="px-4 py-3 text-center font-medium text-muted-foreground text-xs uppercase tracking-wide">
                                    Unit
                                </th>
                                <th class="px-4 py-3 text-center font-medium text-muted-foreground text-xs uppercase tracking-wide">
                                    Stock On Hand
                                </th>
                                <th class="px-4 py-3 text-right font-medium text-muted-foreground text-xs uppercase tracking-wide">
                                    Cost Price
                                </th>
                                <th class="px-4 py-3 text-right font-medium text-muted-foreground text-xs uppercase tracking-wide">
                                    Sale Price
                                </th>
                                <th class="px-4 py-3 text-center font-medium text-muted-foreground text-xs uppercase tracking-wide">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-center font-medium text-muted-foreground text-xs uppercase tracking-wide">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <!-- Loading skeleton -->
                            <template v-if="products.length === 0">
                                <tr>
                                    <td colspan="10" class="h-40 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center gap-2">
                                            <Package class="h-10 w-10 opacity-20" />
                                            <span>No products found.</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr
                                v-for="product in products"
                                :key="product.id"
                                class="hover:bg-muted/30 transition-colors"
                                :class="selectedIds.has(product.id) ? 'bg-primary/5' : ''"
                            >
                                <!-- Checkbox -->
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-input"
                                        :checked="selectedIds.has(product.id)"
                                        @change="toggleRow(product.id)"
                                    />
                                </td>

                                <!-- Product Code + thumbnail -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-md overflow-hidden bg-muted shrink-0 flex items-center justify-center">
                                            <img
                                                v-if="product.image_url"
                                                :src="product.image_url"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                                alt=""
                                            />
                                            <Image v-else class="h-4 w-4 text-muted-foreground" />
                                        </div>
                                        <div>
                                            <p class="font-mono font-medium text-xs">{{ product.sku }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Product Name + brand -->
                                <td class="px-4 py-3 max-w-[200px]">
                                    <p class="font-medium truncate">{{ product.nama_produk }}</p>
                                    <p class="text-xs text-muted-foreground truncate">
                                        {{ product.brand?.nama_brand || '—' }}
                                    </p>
                                </td>

                                <!-- Category -->
                                <td class="px-4 py-3">
                                    <span
                                        v-if="product.kategori"
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                        :class="categoryColor(product.kategori.nama_kategori)"
                                    >
                                        {{ product.kategori.nama_kategori }}
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>

                                <!-- Unit -->
                                <td class="px-4 py-3 text-center text-muted-foreground text-xs">PCS</td>

                                <!-- Stock On Hand + movement -->
                                <td class="px-4 py-3 text-center">
                                    <p class="font-semibold text-sm">
                                        {{ formatNumber(product.stok_on_hand) }}
                                    </p>
                                    <div
                                        class="flex items-center justify-center gap-0.5 text-[11px] font-medium mt-0.5"
                                        :class="netMovement(product) > 0 ? 'text-green-600' : netMovement(product) < 0 ? 'text-red-500' : 'text-muted-foreground'"
                                    >
                                        <component
                                            :is="netMovement(product) > 0 ? ArrowUpRight : netMovement(product) < 0 ? ArrowDownRight : Minus"
                                            class="h-3 w-3"
                                        />
                                        <span>{{ Math.abs(netMovement(product)) }}</span>
                                    </div>
                                </td>

                                <!-- Cost Price -->
                                <td class="px-4 py-3 text-right font-medium text-sm">
                                    <span v-if="Number(product.avg_hpp) > 0">
                                        {{ formatCurrency(product.avg_hpp) }}
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>

                                <!-- Sale Price -->
                                <td class="px-4 py-3 text-right font-medium text-sm">
                                    <span v-if="Number(product.harga_jual_display) > 0">
                                        {{ formatCurrency(product.harga_jual_display) }}
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3 text-center">
                                    <Badge
                                        :variant="statusVariant(Number(product.stok_on_hand))"
                                        class="text-xs whitespace-nowrap"
                                    >
                                        {{ statusLabel(Number(product.stok_on_hand)) }}
                                    </Badge>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-7 w-7"
                                            title="Edit product"
                                            @click="router.visit('/app/admin/master-data/produk')"
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-7 w-7"
                                            title="More"
                                            disabled
                                        >
                                            <MoreHorizontal class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination footer -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-6 py-4 border-t">
                    <!-- Showing info -->
                    <p class="text-sm text-muted-foreground">
                        Showing {{ pagination.from ?? 0 }} to {{ pagination.to ?? 0 }}
                        of {{ formatNumber(pagination.total) }} entries
                    </p>

                    <div class="flex items-center gap-3">
                        <!-- Per page selector -->
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <select
                                v-model="perPage"
                                class="h-8 rounded-md border border-input bg-background px-2 text-sm"
                            >
                                <option value="10">10 / page</option>
                                <option value="25">25 / page</option>
                                <option value="50">50 / page</option>
                                <option value="100">100 / page</option>
                            </select>
                        </div>

                        <!-- Page buttons -->
                        <div class="flex items-center gap-1">
                            <Button
                                variant="outline"
                                size="icon"
                                class="h-8 w-8"
                                :disabled="pagination.current_page <= 1"
                                @click="goToPage(pagination.current_page - 1)"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </Button>

                            <template v-for="p in pageRange" :key="String(p)">
                                <Button
                                    v-if="p !== '...'"
                                    :variant="pagination.current_page === p ? 'default' : 'outline'"
                                    size="icon"
                                    class="h-8 w-8 text-xs"
                                    @click="goToPage(Number(p))"
                                >
                                    {{ p }}
                                </Button>
                                <span v-else class="px-1 text-muted-foreground text-sm">…</span>
                            </template>

                            <Button
                                variant="outline"
                                size="icon"
                                class="h-8 w-8"
                                :disabled="pagination.current_page >= pagination.last_page"
                                @click="goToPage(pagination.current_page + 1)"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>

        </div>
    </AppLayout>
</template>
