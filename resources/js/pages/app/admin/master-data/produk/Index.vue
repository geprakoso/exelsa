<script setup lang="ts">
import AppLayout from '@/components/layout/AppLayout.vue'
import { ref, computed } from 'vue'
import { usePage, useForm } from '@inertiajs/vue3'
import { Plus, Search, Pencil, Trash2, Image } from 'lucide-vue-next'
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
import RelationSelect from '@/components/forms/RelationSelect.vue'

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
}

const page = usePage<{
    produks: { data: Produk[]; current_page: number; last_page: number; per_page: number; total: number }
    brands: { id: number; nama_brand: string }[]
    kategoris: { id: number; nama_kategori: string }[]
}>()

const produks = computed(() => page.props.produks?.data || [])
const pagination = computed(() => ({
    current_page: page.props.produks?.current_page || 1,
    last_page: page.props.produks?.last_page || 1,
    per_page: page.props.produks?.per_page || 15,
    total: page.props.produks?.total || 0,
}))

const isLoading = ref(false)
const showDrawer = ref(false)
const showDeleteModal = ref(false)
const selectedProduk = ref<Produk | null>(null)
const searchQuery = ref('')

const columns = [
    { key: 'image_url', label: 'Photo', sortable: false },
    { key: 'nama_produk', label: 'Product Name', sortable: true },
    { key: 'sku', label: 'SKU', sortable: true },
    { key: 'brand', label: 'Brand', sortable: true },
    { key: 'kategori', label: 'Category', sortable: true },
    { key: 'berat', label: 'Weight', sortable: false },
    { key: 'dimensi', label: 'Dimensions', sortable: false },
]

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
    image_url: '',
})

const brandOptions = computed(() =>
    (page.props.brands || []).map((b: any) => ({ label: b.nama_brand, value: b.id }))
)

const kategoriOptions = computed(() =>
    (page.props.kategoris || []).map((k: any) => ({ label: k.nama_kategori, value: k.id }))
)

function openCreateDrawer() {
    selectedProduk.value = null
    form.reset()
    form.clearErrors()
    showDrawer.value = true
}

function openEditDrawer(produk: Produk) {
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
    form.image_url = produk.image_url || ''
    showDrawer.value = true
}

function openDeleteModal(produk: Produk) {
    selectedProduk.value = produk
    showDeleteModal.value = true
}

function handleSort(field: string, direction: 'asc' | 'desc') {
    // TODO: implement server-side sort
}

function handlePageChange(page: number) {
    // TODO: implement server-side pagination
}

function handleRowClick(produk: Produk) {
    openEditDrawer(produk)
}

function submitForm() {
    if (selectedProduk.value) {
        form.put(`/app/admin/master-data/produk/${selectedProduk.value.id}`, {
            onSuccess: () => {
                showDrawer.value = false
                form.reset()
            },
        })
    } else {
        form.post('/app/admin/master-data/produk', {
            onSuccess: () => {
                showDrawer.value = false
                form.reset()
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
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="Products"
                description="Manage your product catalog and inventory."
                :breadcrumbs="[
                    { label: 'Master Data', href: '/app/admin/master-data' },
                    { label: 'Products' }
                ]"
            >
                <template #actions>
                    <Button @click="openCreateDrawer">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Product
                    </Button>
                </template>
            </PageHeader>
            
            <Card class="p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search products..."
                            class="pl-10"
                        />
                    </div>
                </div>
                
                <DataTable
                    :data="produks"
                    :columns="columns"
                    :pagination="pagination"
                    :loading="isLoading"
                    @sort="handleSort"
                    @page-change="handlePageChange"
                    @row-click="handleRowClick"
                >
                    <template #cell:image_url="{ row }">
                        <div class="h-10 w-10 rounded-md overflow-hidden bg-muted">
                            <img
                                v-if="row.original.image_url"
                                :src="row.original.image_url"
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center">
                                <Image class="h-5 w-5 text-muted-foreground" />
                            </div>
                        </div>
                    </template>
                    
                    <template #cell:brand="{ row }">
                        <Badge v-if="row.original.brand" variant="secondary">
                            {{ row.original.brand.nama_brand }}
                        </Badge>
                        <span v-else class="text-muted-foreground">-</span>
                    </template>
                    
                    <template #cell:kategori="{ row }">
                        <Badge v-if="row.original.kategori" variant="info">
                            {{ row.original.kategori.nama_kategori }}
                        </Badge>
                        <span v-else class="text-muted-foreground">-</span>
                    </template>
                    
                    <template #cell:berat="{ row }">
                        {{ row.original.berat ? row.original.berat + ' gram' : '-' }}
                    </template>
                    
                    <template #cell:dimensi="{ row }">
                        {{ formatDimensi(row.original.panjang, row.original.lebar, row.original.tinggi) }}
                    </template>
                </DataTable>
            </Card>
        </div>
        
        <Sheet :open="showDrawer" @update:open="showDrawer = $event" class="w-[600px]">
            <SheetContent>
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ selectedProduk ? 'Edit Product' : 'Create Product' }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{ selectedProduk ? 'Update product information' : 'Add a new product to your catalog' }}
                        </p>
                    </div>
                    
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <FormField label="Product Name" name="nama_produk" required>
                            <Input v-model="form.nama_produk" placeholder="Enter product name" />
                            <p v-if="form.errors.nama_produk" class="text-sm text-red-500">{{ form.errors.nama_produk }}</p>
                        </FormField>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <FormField label="Brand" name="brand_id">
                                <RelationSelect
                                    v-model="form.brand_id"
                                    :options="brandOptions"
                                    placeholder="Select brand"
                                />
                            </FormField>
                            
                            <FormField label="Category" name="kategori_id">
                                <RelationSelect
                                    v-model="form.kategori_id"
                                    :options="kategoriOptions"
                                    placeholder="Select category"
                                />
                            </FormField>
                        </div>
                        
                        <FormField label="SKU" name="sku">
                            <Input v-model="form.sku" placeholder="Auto-generated if empty" />
                            <p v-if="form.errors.sku" class="text-sm text-red-500">{{ form.errors.sku }}</p>
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
                            <Input v-model.number="form.berat" type="number" min="0" placeholder="Weight in grams" />
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
                        
                        <FormField label="Image URL" name="image_url">
                            <Input v-model="form.image_url" placeholder="https://..." />
                        </FormField>
                        
                        <div class="flex justify-end gap-2 pt-4 border-t">
                            <Button type="button" variant="outline" @click="showDrawer = false">
                                Cancel
                            </Button>
                            <Button type="submit" :loading="form.processing">
                                {{ selectedProduk ? 'Update' : 'Create' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </SheetContent>
        </Sheet>
        
        <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event" class="max-w-md">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Delete Product</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete <strong>{{ selectedProduk?.nama_produk }}</strong>?
                    This action cannot be undone.
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="showDeleteModal = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteProduk" :loading="form.processing">
                        Delete
                    </Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>