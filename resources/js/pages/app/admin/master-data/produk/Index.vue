<script setup lang="ts">
import AppLayout from '@/components/layout/AppLayout.vue'
import { computed, ref, watch } from 'vue'
import { usePage, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import { Plus, Search, Pencil, Trash2, Image, X, LayoutPanelLeft, Maximize2 } from 'lucide-vue-next'

import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import DataTable from '@/components/tables/DataTable.vue'
import Dialog from '@/components/ui/dialog.vue'
import Sheet from '@/components/ui/sheet/index.vue'
import SheetContent from '@/components/ui/sheet/sheet.vue'
import FormField from '@/components/forms/FormField.vue'
import RelationSelect, { type SelectOption } from '@/components/forms/RelationSelect.vue'
import MultiImageUpload, { type ProdukImage } from '@/components/ui/MultiImageUpload.vue'

// Axios instance dengan CSRF token yang aman
const api = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
})

// Set CSRF token setelah DOM ready
if (typeof document !== 'undefined') {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (token) {
        api.defaults.headers.common['X-CSRF-TOKEN'] = token
    }
}

interface Produk {
    id: number
    nama_produk: string
    sku: string
    sn: string | null
    garansi: string | null
    brand: { id: number; nama_brand: string } | null
    kategori: { id: number; nama_kategori: string } | null
    berat: number | null
    panjang: number | null
    lebar: number | null
    tinggi: number | null
    deskripsi: string | null
    image_url: string | null
    images?: ProdukImage[]
}

interface PaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

interface PageProps {
    produks: {
        data: Produk[]
    } & PaginationMeta
    brands: { id: number; nama_brand: string }[]
    kategoris: { id: number; nama_kategori: string }[]
    filters: {
        search?: string
        brand_id?: string
        kategori_id?: string
    }
}

type ViewMode = 'sheet' | 'modal'

const page = usePage<PageProps>()

// Data dari props
const produks = computed(() => page.props.produks.data)
const paginationMeta = computed(() => ({
    current_page: page.props.produks.current_page,
    last_page: page.props.produks.last_page,
    per_page: page.props.produks.per_page,
    total: page.props.produks.total,
}))

// Search dengan debounce
const searchQuery = ref(page.props.filters.search || '')
let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch(searchQuery, (newValue) => {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(
            '/app/admin/master-data/produk',
            { search: newValue || undefined },
            { preserveState: true, replace: true }
        )
    }, 300)
})

// Loading state
const isLoading = ref(false)

// Form state
const showForm = ref(false)
const showDeleteModal = ref(false)
const selectedProduk = ref<Produk | null>(null)
const viewMode = ref<ViewMode>('sheet')

// Load saved view mode preference
try {
    const saved = localStorage.getItem('produk-view-mode') as ViewMode
    if (saved && ['sheet', 'modal'].includes(saved)) {
        viewMode.value = saved
    }
} catch {
    // Ignore localStorage errors
}

watch(viewMode, (mode) => {
    try {
        localStorage.setItem('produk-view-mode', mode)
    } catch {
        // Ignore localStorage errors
    }
})

// Create Brand/Category Modal State
const showCreateBrandModal = ref(false)
const showCreateKategoriModal = ref(false)
const newBrandName = ref('')
const newKategoriName = ref('')

// Table columns
const columns = [
    { key: 'image_url', label: 'Photo', sortable: false },
    { key: 'nama_produk', label: 'Product Name', sortable: true },
    { key: 'sku', label: 'SKU', sortable: true },
    { key: 'brand', label: 'Brand', sortable: true },
    { key: 'kategori', label: 'Category', sortable: true },
    { key: 'berat', label: 'Weight', sortable: false },
    { key: 'dimensi', label: 'Dimensions', sortable: false },
]

// Optimized produk data dengan pre-computed primary image
const processedProduks = computed(() =>
    produks.value.map((p) => ({
        ...p,
        primaryImage:
            p.images?.find((img) => img.is_primary)?.url ||
            p.images?.[0]?.url ||
            null,
    }))
)

// Image files state
const selectedImageFiles = ref<File[]>([])
const imageUploadRef = ref<InstanceType<typeof MultiImageUpload> | null>(null)

const form = useForm({
    nama_produk: '',
    kategori_id: null as number | null,
    brand_id: null as number | null,
    sku: '',
    sn: '',
    garansi: '',
    berat: null as number | null,
    panjang: null as number | null,
    lebar: null as number | null,
    tinggi: null as number | null,
    deskripsi: '',
})

const brandOptions = computed<SelectOption[]>(() =>
    (page.props.brands || []).map((b) => ({
        label: b.nama_brand,
        value: b.id,
    }))
)

const kategoriOptions = computed<SelectOption[]>(() =>
    (page.props.kategoris || []).map((k) => ({
        label: k.nama_kategori,
        value: k.id,
    }))
)

function openCreateForm() {
    selectedProduk.value = null
    form.reset()
    form.clearErrors()
    selectedImageFiles.value = []
    imageUploadRef.value?.clearAllPreviews()
    showForm.value = true
}

function openEditForm(produk: Produk) {
    selectedProduk.value = produk
    form.nama_produk = produk.nama_produk
    form.sku = produk.sku || ''
    form.sn = produk.sn || ''
    form.garansi = produk.garansi || ''
    form.brand_id = produk.brand?.id || null
    form.kategori_id = produk.kategori?.id || null
    form.berat = produk.berat
    form.panjang = produk.panjang
    form.lebar = produk.lebar
    form.tinggi = produk.tinggi
    form.deskripsi = produk.deskripsi || ''
    selectedImageFiles.value = []
    showForm.value = true
}

function closeForm() {
    showForm.value = false
}

function openDeleteModal(produk: Produk) {
    selectedProduk.value = produk
    showDeleteModal.value = true
}

function handleSort(field: string, direction: 'asc' | 'desc') {
    // TODO: Implement server-side sort
    console.log('Sort:', field, direction)
}

function handlePageChange(pageNum: number) {
    isLoading.value = true
    router.get(
        '/app/admin/master-data/produk',
        {
            page: pageNum,
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            onFinish: () => {
                isLoading.value = false
            },
        }
    )
}

function handleRowClick(produk: Produk) {
    openEditForm(produk)
}

function submitForm() {
    const formData = new FormData()

    const formData_obj = form.data() as Record<string, any>
    Object.keys(formData_obj).forEach((key) => {
        const value = formData_obj[key]
        if (value !== null && value !== undefined && value !== '') {
            formData.append(key, String(value))
        }
    })

    selectedImageFiles.value.forEach((file, index) => {
        formData.append(`images[${index}]`, file)
    })

    if (selectedProduk.value) {
        router.post(
            `/app/admin/master-data/produk/${selectedProduk.value.id}`,
            formData,
            {
                onSuccess: () => {
                    showForm.value = false
                    form.reset()
                    selectedImageFiles.value = []
                    imageUploadRef.value?.clearAllPreviews()
                },
                onError: (errors) => {
                    console.error('Update error:', errors)
                },
            }
        )
    } else {
        router.post('/app/admin/master-data/produk', formData, {
            onSuccess: () => {
                showForm.value = false
                form.reset()
                selectedImageFiles.value = []
                imageUploadRef.value?.clearAllPreviews()
            },
            onError: (errors) => {
                console.error('Create error:', errors)
            },
        })
    }
}

function deleteProduk() {
    if (selectedProduk.value) {
        form.delete(`/app/admin/master-data/produk/${selectedProduk.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false
                selectedProduk.value = null
            },
        })
    }
}

function formatDimensi(p: number | null, l: number | null, t: number | null): string {
    if (!p && !l && !t) return '-'
    return `${p || 0} x ${l || 0} x ${t || 0} cm`
}

function toggleViewMode(mode: ViewMode) {
    viewMode.value = mode
}

// Image Operations
const isImageOperationLoading = ref(false)

async function handleSetPrimary(imageId: number) {
    if (!selectedProduk.value || isImageOperationLoading.value) return

    isImageOperationLoading.value = true
    try {
        await api.post(`/api/produk-images/${imageId}/primary`)

        if (selectedProduk.value.images) {
            selectedProduk.value.images = selectedProduk.value.images.map((img) => ({
                ...img,
                is_primary: img.id === imageId,
            }))
        }

        await router.reload({ only: ['produks'], preserveState: true })
    } catch (error) {
        console.error('Failed to set primary image:', error)
        alert('Failed to set primary image')
    } finally {
        isImageOperationLoading.value = false
    }
}

async function handleDeleteImage(imageId: number) {
    if (!confirm('Are you sure you want to delete this image?')) return
    if (isImageOperationLoading.value) return

    isImageOperationLoading.value = true
    try {
        await api.delete(`/api/produk-images/${imageId}`)

        if (selectedProduk.value?.images) {
            selectedProduk.value.images = selectedProduk.value.images.filter(
                (img) => img.id !== imageId
            )
        }

        await router.reload({ only: ['produks'], preserveState: true })
    } catch (error) {
        console.error('Failed to delete image:', error)
        alert('Failed to delete image')
    } finally {
        isImageOperationLoading.value = false
    }
}

async function handleReorder(imageIds: number[]) {
    if (!selectedProduk.value || isImageOperationLoading.value) return

    isImageOperationLoading.value = true
    try {
        await api.post(`/api/produk/${selectedProduk.value.id}/images/reorder`, {
            images: imageIds,
        })

        if (selectedProduk.value.images) {
            const imageMap = new Map(selectedProduk.value.images.map((img) => [img.id, img]))
            selectedProduk.value.images = imageIds
                .map((id) => imageMap.get(id))
                .filter((img): img is ProdukImage => img !== undefined)
        }

        await router.reload({ only: ['produks'], preserveState: true })
    } catch (error) {
        console.error('Failed to reorder images:', error)
        alert('Failed to reorder images')
    } finally {
        isImageOperationLoading.value = false
    }
}

// Create Brand/Kategori Handlers
function openCreateBrandModal(name: string) {
    newBrandName.value = name
    showCreateBrandModal.value = true
}

function openCreateKategoriModal(name: string) {
    newKategoriName.value = name
    showCreateKategoriModal.value = true
}

function closeCreateBrandModal() {
    showCreateBrandModal.value = false
    newBrandName.value = ''
}

function closeCreateKategoriModal() {
    showCreateKategoriModal.value = false
    newKategoriName.value = ''
}

async function handleCreateBrand() {
    if (!newBrandName.value.trim()) return

    try {
        await router.post('/app/admin/master-data/brand', {
            nama_brand: newBrandName.value.trim(),
        }, {
            preserveState: true,
        })
        closeCreateBrandModal()
        router.reload({ only: ['brands'], preserveState: true })
    } catch (error: any) {
        console.error('Failed to create brand:', error)
    }
}

async function handleCreateKategori() {
    if (!newKategoriName.value.trim()) return

    try {
        await router.post('/app/admin/master-data/kategori', {
            nama_kategori: newKategoriName.value.trim(),
        }, {
            preserveState: true,
        })
        closeCreateKategoriModal()
        router.reload({ only: ['kategoris'], preserveState: true })
    } catch (error: any) {
        console.error('Failed to create kategori:', error)
    }
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="Products"
                description="Manage your product catalog and inventory."
                :breadcrumbs="[
                    { label: 'Master Data', href: '/app/admin/master-data' },
                    { label: 'Products' },
                ]"
            >
                <template #actions>
                    <Button @click="openCreateForm" class="gap-2">
                        <Plus class="h-4 w-4" />
                        <span class="hidden sm:inline">Create Product</span>
                        <span class="sm:hidden">Create</span>
                    </Button>
                </template>
            </PageHeader>

            <Card class="p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="relative flex-1 max-w-sm">
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search products..."
                            class="pl-10"
                        />
                    </div>
                </div>

                <DataTable
                    :data="processedProduks"
                    :columns="columns"
                    :pagination="paginationMeta"
                    :loading="isLoading"
                    @sort="handleSort"
                    @page-change="handlePageChange"
                    @row-click="handleRowClick"
                >
                    <template #cell:image_url="{ row }">
                        <div class="h-10 w-10 rounded-md overflow-hidden bg-muted">
                            <img
                                v-if="row.primaryImage"
                                :src="row.primaryImage"
                                class="h-full w-full object-cover"
                                loading="lazy"
                                alt=""
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center"
                            >
                                <Image class="h-5 w-5 text-muted-foreground" />
                            </div>
                        </div>
                    </template>

                    <template #cell:brand="{ row }">
                        <Badge v-if="row.brand" variant="secondary">
                            {{ row.brand.nama_brand }}
                        </Badge>
                        <span v-else class="text-muted-foreground">-</span>
                    </template>

                    <template #cell:kategori="{ row }">
                        <Badge v-if="row.kategori" variant="info">
                            {{ row.kategori.nama_kategori }}
                        </Badge>
                        <span v-else class="text-muted-foreground">-</span>
                    </template>

                    <template #cell:berat="{ row }">
                        {{ row.berat ? row.berat + ' gram' : '-' }}
                    </template>

                    <template #cell:dimensi="{ row }">
                        {{ formatDimensi(row.panjang, row.lebar, row.tinggi) }}
                    </template>

                    <template #actions="{ row }">
                        <div class="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click.stop="openEditForm(row)"
                            >
                                <Pencil class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click.stop="openDeleteModal(row)"
                            >
                                <Trash2 class="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                    </template>
                </DataTable>
            </Card>
        </div>

        <!-- Sheet/Slide Canvas Mode -->
        <Sheet v-if="viewMode === 'sheet'">
            <SheetContent
                :open="showForm"
                @update:open="showForm = $event"
                class="w-[600px] sm:max-w-[600px]"
            >
                <div class="space-y-6 h-full flex flex-col">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold">
                                {{ selectedProduk ? 'Edit Product' : 'Create Product' }}
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    selectedProduk
                                        ? 'Update product information'
                                        : 'Add a new product to your catalog'
                                }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div
                                class="flex items-center gap-1 rounded-lg border bg-muted p-1"
                            >
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7"
                                    :class="
                                        viewMode === 'sheet'
                                            ? 'bg-background shadow-sm'
                                            : 'hover:bg-transparent'
                                    "
                                    @click="toggleViewMode('sheet')"
                                    title="Slide Canvas Mode"
                                >
                                    <LayoutPanelLeft class="h-3.5 w-3.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7"
                                    :class="
                                        viewMode === 'modal'
                                            ? 'bg-background shadow-sm'
                                            : 'hover:bg-transparent'
                                    "
                                    @click="toggleViewMode('modal')"
                                    title="Modal/Popup Mode"
                                >
                                    <Maximize2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="shrink-0"
                                @click="closeForm"
                                title="Close"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <form
                        @submit.prevent="submitForm"
                        class="space-y-4 flex-1 overflow-y-auto pr-2"
                    >
                        <FormField
                            label="Product Name"
                            name="nama_produk"
                            required
                        >
                            <Input
                                v-model="form.nama_produk"
                                placeholder="Enter product name"
                            />
                            <p
                                v-if="form.errors.nama_produk"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.nama_produk }}
                            </p>
                        </FormField>

                        <div class="grid grid-cols-2 gap-4">
                            <FormField label="Brand" name="brand_id">
                                <RelationSelect
                                    v-model="form.brand_id"
                                    :options="brandOptions"
                                    placeholder="Select brand"
                                    :enable-create="true"
                                    create-label="Create brand"
                                    @create="openCreateBrandModal"
                                />
                            </FormField>

                            <FormField label="Category" name="kategori_id">
                                <RelationSelect
                                    v-model="form.kategori_id"
                                    :options="kategoriOptions"
                                    placeholder="Select category"
                                    :enable-create="true"
                                    create-label="Create category"
                                    @create="openCreateKategoriModal"
                                />
                            </FormField>
                        </div>

                        <FormField label="SKU" name="sku">
                            <Input
                                v-model="form.sku"
                                placeholder="Auto-generated if empty"
                            />
                            <p v-if="form.errors.sku" class="text-sm text-red-500">
                                {{ form.errors.sku }}
                            </p>
                        </FormField>

                        <div class="grid grid-cols-2 gap-4">
                            <FormField label="Serial Number" name="sn">
                                <Input v-model="form.sn" placeholder="SN (optional)" />
                            </FormField>

                            <FormField label="Warranty" name="garansi">
                                <Input
                                    v-model="form.garansi"
                                    placeholder="Warranty period"
                                />
                            </FormField>
                        </div>

                        <FormField label="Weight (gram)" name="berat">
                            <Input
                                v-model.number="form.berat"
                                type="number"
                                min="0"
                                placeholder="Weight in grams"
                            />
                        </FormField>

                        <div class="grid grid-cols-3 gap-4">
                            <FormField label="Length (cm)" name="panjang">
                                <Input
                                    v-model.number="form.panjang"
                                    type="number"
                                    min="0"
                                />
                            </FormField>
                            <FormField label="Width (cm)" name="lebar">
                                <Input
                                    v-model.number="form.lebar"
                                    type="number"
                                    min="0"
                                />
                            </FormField>
                            <FormField label="Height (cm)" name="tinggi">
                                <Input
                                    v-model.number="form.tinggi"
                                    type="number"
                                    min="0"
                                />
                            </FormField>
                        </div>

                        <FormField label="Description" name="deskripsi">
                            <textarea
                                v-model="form.deskripsi"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Product description"
                                rows="3"
                            ></textarea>
                        </FormField>

                        <FormField label="Product Images" name="images">
                            <MultiImageUpload
                                ref="imageUploadRef"
                                v-model="selectedImageFiles"
                                :produk-id="selectedProduk?.id"
                                :existing-images="selectedProduk?.images || []"
                                :max-images="10"
                                @set-primary="handleSetPrimary"
                                @delete="handleDeleteImage"
                                @reorder="handleReorder"
                            />
                        </FormField>
                    </form>

                    <div class="flex justify-end gap-2 pt-4 border-t">
                        <Button type="button" variant="outline" @click="closeForm">
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :loading="form.processing"
                            @click="submitForm"
                        >
                            {{ selectedProduk ? 'Update Product' : 'Create Product' }}
                        </Button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Modal/Dialog Mode -->
        <Dialog
            v-else
            :open="showForm"
            @update:open="showForm = $event"
            class="max-w-2xl max-h-[90vh]"
        >
            <div class="space-y-6 flex flex-col max-h-[calc(90vh-3rem)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ selectedProduk ? 'Edit Product' : 'Create Product' }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{
                                selectedProduk
                                    ? 'Update product information'
                                    : 'Add a new product to your catalog'
                            }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 -mr-2 -mt-2">
                        <div
                            class="flex items-center gap-1 rounded-lg border bg-muted p-1"
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-7 w-7"
                                :class="
                                    viewMode === 'sheet'
                                        ? 'bg-background shadow-sm'
                                        : 'hover:bg-transparent'
                                "
                                @click="toggleViewMode('sheet')"
                                title="Slide Canvas Mode"
                            >
                                <LayoutPanelLeft class="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-7 w-7"
                                :class="
                                    viewMode === 'modal'
                                        ? 'bg-background shadow-sm'
                                        : 'hover:bg-transparent'
                                "
                                @click="toggleViewMode('modal')"
                                title="Modal/Popup Mode"
                            >
                                <Maximize2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="shrink-0"
                            @click="closeForm"
                            title="Close"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <form
                    @submit.prevent="submitForm"
                    class="space-y-4 overflow-y-auto pr-2"
                >
                    <FormField label="Product Name" name="nama_produk" required>
                        <Input
                            v-model="form.nama_produk"
                            placeholder="Enter product name"
                        />
                        <p
                            v-if="form.errors.nama_produk"
                            class="text-sm text-red-500"
                        >
                            {{ form.errors.nama_produk }}
                        </p>
                    </FormField>

                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Brand" name="brand_id" required>
                            <RelationSelect
                                v-model="form.brand_id"
                                :options="brandOptions"
                                placeholder="Select brand..."
                                search-placeholder="Search brand..."
                                empty-message="No brands found"
                                icon="building"
                                :clearable="true"
                                :enable-create="true"
                                create-label="Create brand"
                                @create="openCreateBrandModal"
                            />
                        </FormField>

                        <FormField label="Category" name="kategori_id" required>
                            <RelationSelect
                                v-model="form.kategori_id"
                                :options="kategoriOptions"
                                placeholder="Select category..."
                                search-placeholder="Search category..."
                                empty-message="No categories found"
                                icon="tag"
                                :clearable="true"
                                :enable-create="true"
                                create-label="Create category"
                                @create="openCreateKategoriModal"
                            />
                        </FormField>
                    </div>

                    <FormField label="SKU" name="sku">
                        <Input v-model="form.sku" placeholder="Auto-generated if empty" />
                        <p v-if="form.errors.sku" class="text-sm text-red-500">
                            {{ form.errors.sku }}
                        </p>
                    </FormField>

                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Serial Number" name="sn">
                            <Input v-model="form.sn" placeholder="SN (optional)" />
                        </FormField>

                        <FormField label="Warranty" name="garansi">
                            <Input v-model="form.garansi" placeholder="Warranty period" />
                        </FormField>
                    </div>

                    <FormField label="Weight (gram)" name="berat">
                        <Input
                            v-model.number="form.berat"
                            type="number"
                            min="0"
                            placeholder="Weight in grams"
                        />
                    </FormField>

                    <div class="grid grid-cols-3 gap-4">
                        <FormField label="Length (cm)" name="panjang">
                            <Input v-model.number="form.panjang" type="number" min="0" />
                        </FormField>
                        <FormField label="Width (cm)" name="lebar">
                            <Input v-model.number="form.lebar" type="number" min="0" />
                        </FormField>
                        <FormField label="Height (cm)" name="tinggi">
                            <Input v-model.number="form.tinggi" type="number" min="0" />
                        </FormField>
                    </div>

                    <FormField label="Description" name="deskripsi">
                        <textarea
                            v-model="form.deskripsi"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Product description"
                            rows="3"
                        ></textarea>
                    </FormField>

                    <FormField label="Product Images" name="images">
                        <MultiImageUpload
                            ref="imageUploadRef"
                            v-model="selectedImageFiles"
                            :produk-id="selectedProduk?.id"
                            :existing-images="selectedProduk?.images || []"
                            :max-images="10"
                            @set-primary="handleSetPrimary"
                            @delete="handleDeleteImage"
                            @reorder="handleReorder"
                        />
                    </FormField>
                </form>

                <div class="flex justify-end gap-2 pt-4 border-t">
                    <Button type="button" variant="outline" @click="closeForm">
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :loading="form.processing"
                        @click="submitForm"
                    >
                        {{ selectedProduk ? 'Update Product' : 'Create Product' }}
                    </Button>
                </div>
            </div>
        </Dialog>

        <Dialog
            :open="showDeleteModal"
            @update:open="showDeleteModal = $event"
            class="max-w-md"
        >
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Delete Product</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete
                    <strong>{{ selectedProduk?.nama_produk }}</strong>? This action
                    cannot be undone.
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="showDeleteModal = false">
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="deleteProduk"
                        :loading="form.processing"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </Dialog>

        <!-- Create Brand Modal -->
        <Dialog
            :open="showCreateBrandModal"
            @update:open="showCreateBrandModal = $event"
            class="max-w-md z-[60]"
        >
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Create New Brand</h2>
                <p class="text-muted-foreground">
                    Brand "<strong>{{ newBrandName }}</strong>" not found. Do you want
                    to create it?
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="closeCreateBrandModal">
                        Cancel
                    </Button>
                    <Button @click="handleCreateBrand" :loading="form.processing">
                        Create Brand
                    </Button>
                </div>
            </div>
        </Dialog>

        <!-- Create Category Modal -->
        <Dialog
            :open="showCreateKategoriModal"
            @update:open="showCreateKategoriModal = $event"
            class="max-w-md z-[60]"
        >
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Create New Category</h2>
                <p class="text-muted-foreground">
                    Category "<strong>{{ newKategoriName }}</strong>" not found. Do you
                    want to create it?
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="closeCreateKategoriModal">
                        Cancel
                    </Button>
                    <Button @click="handleCreateKategori" :loading="form.processing">
                        Create Category
                    </Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
