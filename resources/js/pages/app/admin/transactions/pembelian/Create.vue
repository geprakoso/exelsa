<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import Dialog from '@/components/ui/dialog.vue'
import FormField from '@/components/forms/FormField.vue'
import { ArrowLeft, Plus, Trash2, Package, Save } from 'lucide-vue-next'
import ProdukSelect, { type ProdukOption } from '@/components/forms/ProdukSelect.vue'
import RelationSelect, { type SelectOption } from '@/components/forms/RelationSelect.vue'

const page = usePage()

const suppliers = computed(() => page.props.suppliers || [])
const jenisPembayaranOptions = computed(() => page.props.jenisPembayaranOptions || [])

const supplierOptions = ref<SelectOption[]>(
    (suppliers.value as any[]).map((s) => ({
        label: s.nama_supplier,
        value: s.id,
    }))
)

interface ItemRow {
    id: string
    id_produk: number | null
    qty: number
    cost_price: number
    selling_price: number
}

const form = ref({
    tanggal: new Date().toISOString().split('T')[0],
    id_supplier: null as number | null,
    id_karyawan: null as number | null,
    nota_supplier: '',
    catatan: '',
    tipe_pembelian: 'non_ppn',
    jenis_pembayaran: 'lunas',
    tgl_tempo: '',
    items: [] as ItemRow[],
})

const errors = ref<Record<string, string>>({})

const totalCost = computed(() => {
    return form.value.items.reduce((sum, item) => {
        return sum + (item.qty * item.cost_price)
    }, 0)
})

const totalSellingPrice = computed(() => {
    return form.value.items.reduce((sum, item) => {
        return sum + (item.qty * item.selling_price)
    }, 0)
})

const margin = computed(() => {
    return totalSellingPrice.value - totalCost.value
})

function addItem() {
    form.value.items.push({
        id: `new-${Date.now()}`,
        id_produk: null,
        qty: 1,
        cost_price: 0,
        selling_price: 0,
    })
}

function removeItem(index: number) {
    form.value.items.splice(index, 1)
}

function onProductSelect(item: ItemRow, produk: ProdukOption) {
    item.id_produk = produk.id
}

function formatCurrency(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
}

function submit() {
    errors.value = {}
    
    if (form.value.items.length === 0) {
        errors.value.items = 'At least one item is required'
        return
    }
    
    const hasInvalidItem = form.value.items.some(item => !item.id_produk || item.qty <= 0)
    if (hasInvalidItem) {
        errors.value.items = 'All items must have product and quantity > 0'
        return
    }
    
    router.post('/app/admin/transactions/pembelian', {
        tanggal: form.value.tanggal,
        id_supplier: form.value.id_supplier,
        id_karyawan: form.value.id_karyawan,
        nota_supplier: form.value.nota_supplier,
        catatan: form.value.catatan,
        tipe_pembelian: form.value.tipe_pembelian,
        jenis_pembayaran: form.value.jenis_pembayaran,
        tgl_tempo: form.value.tgl_tempo || null,
        items: form.value.items.map(item => ({
            id_produk: item.id_produk,
            qty: item.qty,
            cost_price: item.cost_price,
            selling_price: item.selling_price,
        })),
    }, {
        onError: (err) => {
            errors.value = err
        },
    })
}

if (form.value.items.length === 0) {
    addItem()
}

// --- Supplier Modal ---
const showSupplierModal = ref(false)
const supplierForm = ref({
    nama_supplier: '',
    no_hp: '',
    email: '',
    alamat: '',
    provinsi: '',
    kota: '',
    kecamatan: '',
})
const supplierErrors = ref<Record<string, string>>({})
const supplierSubmitting = ref(false)

const provinces = ref<{ code: string; name: string }[]>([])
const cities = ref<{ code: string; name: string }[]>([])
const districts = ref<{ code: string; name: string }[]>([])
const loadingProvinces = ref(false)
const loadingCities = ref(false)
const loadingDistricts = ref(false)

async function openSupplierModal(initialName: string) {
    supplierForm.value = {
        nama_supplier: initialName,
        no_hp: '',
        email: '',
        alamat: '',
        provinsi: '',
        kota: '',
        kecamatan: '',
    }
    supplierErrors.value = {}
    showSupplierModal.value = true
    await loadProvinces()
}

async function loadProvinces() {
    loadingProvinces.value = true
    try {
        const res = await fetch('/api/indonesia/provinces', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
        provinces.value = await res.json()
    } catch (e) {
        console.error('Failed to load provinces', e)
    } finally {
        loadingProvinces.value = false
    }
}

async function loadCities() {
    const provinceCode = provinces.value.find(p => p.name === supplierForm.value.provinsi)?.code
    if (!provinceCode) {
        cities.value = []
        districts.value = []
        return
    }
    loadingCities.value = true
    try {
        const res = await fetch(`/api/indonesia/cities?province_code=${encodeURIComponent(provinceCode)}`, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
        cities.value = await res.json()
    } catch (e) {
        console.error('Failed to load cities', e)
    } finally {
        loadingCities.value = false
    }
}

async function loadDistricts() {
    const cityCode = cities.value.find(c => c.name === supplierForm.value.kota)?.code
    if (!cityCode) {
        districts.value = []
        return
    }
    loadingDistricts.value = true
    try {
        const res = await fetch(`/api/indonesia/districts?city_code=${encodeURIComponent(cityCode)}`, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
        districts.value = await res.json()
    } catch (e) {
        console.error('Failed to load districts', e)
    } finally {
        loadingDistricts.value = false
    }
}

watch(() => supplierForm.value.provinsi, () => {
    supplierForm.value.kota = ''
    supplierForm.value.kecamatan = ''
    cities.value = []
    districts.value = []
    if (supplierForm.value.provinsi) {
        loadCities()
    }
})

watch(() => supplierForm.value.kota, () => {
    supplierForm.value.kecamatan = ''
    districts.value = []
    if (supplierForm.value.kota) {
        loadDistricts()
    }
})

async function submitSupplierForm() {
    supplierErrors.value = {}
    supplierSubmitting.value = true

    try {
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        const res = await fetch('/app/admin/master-data/supplier', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify(supplierForm.value),
        })

        if (res.status === 422) {
            const data = await res.json()
            supplierErrors.value = data.errors || {}
            return
        }

        if (!res.ok) {
            supplierErrors.value = { nama_supplier: 'Failed to create supplier' }
            return
        }

        const newSupplier = await res.json()
        supplierOptions.value.push({
            label: newSupplier.nama_supplier,
            value: newSupplier.id,
        })
        form.value.id_supplier = newSupplier.id
        showSupplierModal.value = false
    } catch (e) {
        supplierErrors.value = { nama_supplier: 'Network error. Please try again.' }
    } finally {
        supplierSubmitting.value = false
    }
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="New Purchase"
                description="Create a new purchase transaction."
                :breadcrumbs="[
                    { label: 'Transactions', href: '/app/admin/transactions' },
                    { label: 'Pembelian', href: '/app/admin/transactions/pembelian' },
                    { label: 'New Purchase' }
                ]"
            >
                <template #actions>
                    <Link href="/app/admin/transactions/pembelian">
                        <Button variant="outline">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Cancel
                        </Button>
                    </Link>
                </template>
            </PageHeader>
            
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Left: Items + Notes -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Items -->
                        <Card class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-semibold flex items-center gap-2">
                                    <Package class="h-5 w-5" />
                                    Items
                                </h3>
                                <Button type="button" variant="outline" size="sm" @click="addItem">
                                    <Plus class="h-4 w-4 mr-1" />
                                    Add Item
                                </Button>
                            </div>
                            
                            <p v-if="errors.items" class="text-sm text-destructive mb-4">
                                {{ errors.items }}
                            </p>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-muted/50 border-b">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-sm font-medium text-muted-foreground w-2/5">Product</th>
                                            <th class="px-3 py-2 text-center text-sm font-medium text-muted-foreground w-16">Qty</th>
                                            <th class="px-3 py-2 text-right text-sm font-medium text-muted-foreground w-28">Cost</th>
                                            <th class="px-3 py-2 text-right text-sm font-medium text-muted-foreground w-28">Price</th>
                                            <th class="px-3 py-2 text-right text-sm font-medium text-muted-foreground w-32">Subtotal</th>
                                            <th class="px-3 py-2 w-12"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr v-for="(item, index) in form.items" :key="item.id">
                                            <td class="px-3 py-2">
                                                <ProdukSelect
                                                    :model-value="item.id_produk"
                                                    @update:model-value="item.id_produk = $event"
                                                    @select="onProductSelect(item, $event)"
                                                    placeholder="Search product..."
                                                />
                                            </td>
                                            <td class="px-3 py-2">
                                                <Input
                                                    v-model.number="item.qty"
                                                    type="number"
                                                    min="1"
                                                    class="text-center"
                                                />
                                            </td>
                                            <td class="px-3 py-2">
                                                <Input
                                                    v-model.number="item.cost_price"
                                                    type="number"
                                                    min="0"
                                                    class="text-right"
                                                />
                                            </td>
                                            <td class="px-3 py-2">
                                                <Input
                                                    v-model.number="item.selling_price"
                                                    type="number"
                                                    min="0"
                                                    class="text-right"
                                                />
                                            </td>
                                            <td class="px-3 py-2 text-right font-medium">
                                                {{ formatCurrency(item.qty * item.cost_price) }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 text-destructive hover:text-destructive"
                                                    :disabled="form.items.length <= 1"
                                                    @click="removeItem(index)"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </Card>
                        
                        <!-- Notes -->
                        <Card class="p-6">
                            <h3 class="font-semibold mb-4">Notes</h3>
                            <textarea
                                v-model="form.catatan"
                                class="w-full h-24 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Add notes..."
                            />
                        </Card>
                    </div>
                    
                    <!-- Right: Summary + Details -->
                    <div class="space-y-6">
                        <!-- Purchase Details -->
                        <Card class="p-6">
                            <h3 class="font-semibold mb-4">Purchase Details</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm text-muted-foreground block mb-1">Date</label>
                                    <Input
                                        v-model="form.tanggal"
                                        type="date"
                                        class="w-full"
                                    />
                                </div>
                                
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="text-sm text-muted-foreground">Supplier</label>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80 transition-colors"
                                            @click="openSupplierModal('')"
                                        >
                                            <Plus class="h-3.5 w-3.5" />
                                            Add new
                                        </button>
                                    </div>
                                    <RelationSelect
                                        v-model="form.id_supplier"
                                        :options="supplierOptions"
                                        placeholder="Select supplier..."
                                        search-placeholder="Search supplier..."
                                        empty-message="No suppliers found"
                                        icon="building"
                                        enable-create
                                        create-label="Add supplier"
                                        @create="openSupplierModal"
                                    />
                                </div>
                                
                                <div>
                                    <label class="text-sm text-muted-foreground block mb-1">Nota Supplier</label>
                                    <Input
                                        v-model="form.nota_supplier"
                                        placeholder="Invoice number from supplier"
                                        class="w-full"
                                    />
                                </div>
                                
                                <div>
                                    <label class="text-sm text-muted-foreground block mb-1">Payment Type</label>
                                    <select
                                        v-model="form.jenis_pembayaran"
                                        class="w-full h-9 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option
                                            v-for="option in jenisPembayaranOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>
                                
                                <div v-if="form.jenis_pembayaran === 'tempo'">
                                    <label class="text-sm text-muted-foreground block mb-1">Due Date</label>
                                    <Input
                                        v-model="form.tgl_tempo"
                                        type="date"
                                        class="w-full"
                                    />
                                </div>
                            </div>
                        </Card>
                        
                        <!-- Summary -->
                        <Card class="p-6">
                            <h3 class="font-semibold mb-4">Summary</h3>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Total Cost</span>
                                    <span>{{ formatCurrency(totalCost) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Total Selling Price</span>
                                    <span>{{ formatCurrency(totalSellingPrice) }}</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold pt-3 border-t">
                                    <span>Margin</span>
                                    <span :class="margin >= 0 ? 'text-green-600' : 'text-red-600'">{{ formatCurrency(margin) }}</span>
                                </div>
                            </div>
                            
                            <Button
                                type="submit"
                                class="w-full mt-6"
                                size="lg"
                            >
                                <Save class="h-4 w-4 mr-2" />
                                Save Purchase
                            </Button>
                        </Card>
                    </div>
                </div>
            </form>
        </div>

        <!-- Create Supplier Modal -->
        <Dialog
            :open="showSupplierModal"
            @update:open="showSupplierModal = $event"
            class="max-w-2xl"
        >
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Create Supplier</h2>

                <form @submit.prevent="submitSupplierForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <FormField
                            label="Nama Supplier"
                            name="nama_supplier"
                            :error="supplierErrors.nama_supplier?.[0]"
                            required
                            class="col-span-2"
                        >
                            <Input
                                v-model="supplierForm.nama_supplier"
                                placeholder="Enter supplier name"
                            />
                        </FormField>

                        <FormField label="No. HP" name="no_hp" :error="supplierErrors.no_hp?.[0]" required>
                            <Input v-model="supplierForm.no_hp" placeholder="08xxxxxxxxxx" />
                        </FormField>

                        <FormField label="Email" name="email" :error="supplierErrors.email?.[0]">
                            <Input
                                v-model="supplierForm.email"
                                type="email"
                                placeholder="email@supplier.com"
                            />
                        </FormField>

                        <FormField label="Alamat" name="alamat" :error="supplierErrors.alamat?.[0]" class="col-span-2">
                            <textarea
                                v-model="supplierForm.alamat"
                                placeholder="Enter full address"
                                rows="3"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            />
                        </FormField>

                        <FormField label="Provinsi" name="provinsi" :error="supplierErrors.provinsi?.[0]">
                            <select
                                v-model="supplierForm.provinsi"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                :disabled="loadingProvinces"
                            >
                                <option value="" disabled>
                                    {{ loadingProvinces ? 'Loading...' : 'Select province' }}
                                </option>
                                <option v-for="prov in provinces" :key="prov.code" :value="prov.name">
                                    {{ prov.name }}
                                </option>
                            </select>
                        </FormField>

                        <FormField label="Kota" name="kota" :error="supplierErrors.kota?.[0]">
                            <select
                                v-model="supplierForm.kota"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                :disabled="!supplierForm.provinsi || loadingCities"
                            >
                                <option value="" disabled>
                                    {{ loadingCities ? 'Loading...' : 'Select city' }}
                                </option>
                                <option v-for="city in cities" :key="city.code" :value="city.name">
                                    {{ city.name }}
                                </option>
                            </select>
                        </FormField>

                        <FormField label="Kecamatan" name="kecamatan" :error="supplierErrors.kecamatan?.[0]">
                            <select
                                v-model="supplierForm.kecamatan"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                :disabled="!supplierForm.kota || loadingDistricts"
                            >
                                <option value="" disabled>
                                    {{ loadingDistricts ? 'Loading...' : 'Select district' }}
                                </option>
                                <option v-for="dist in districts" :key="dist.code" :value="dist.name">
                                    {{ dist.name }}
                                </option>
                            </select>
                        </FormField>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" @click="showSupplierModal = false">
                            Cancel
                        </Button>
                        <Button type="submit" :loading="supplierSubmitting">
                            Create Supplier
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog>
    </AppLayout>
</template>