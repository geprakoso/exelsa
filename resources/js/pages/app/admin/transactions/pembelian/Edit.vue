<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import { ArrowLeft, Plus, Trash2, Package, Save } from 'lucide-vue-next'

const page = usePage()

const pembelian = computed(() => page.props.pembelian || {})
const suppliers = computed(() => page.props.suppliers || [])
const karyawans = computed(() => page.props.karyawans || [])
const produks = computed(() => page.props.produks || [])
const paymentAccounts = computed(() => page.props.paymentAccounts || [])
const jenisPembayaranOptions = computed(() => page.props.jenisPembayaranOptions || [])

interface ItemRow {
    id?: number
    id_produk: number | null
    qty: number
    harga: number
}

const form = ref({
    tanggal: pembelian.value.tanggal || new Date().toISOString().split('T')[0],
    id_supplier: pembelian.value.id_supplier || null as number | null,
    id_karyawan: pembelian.value.id_karyawan || null as number | null,
    nota_supplier: pembelian.value.nota_supplier || '',
    catatan: pembelian.value.catatan || '',
    tipe_pembelian: pembelian.value.tipe_pembelian || 'barang',
    jenis_pembayaran: pembelian.value.jenis_pembayaran || 'cash',
    tgl_tempo: pembelian.value.tgl_tempo || '',
    items: (pembelian.value.items || []).map((item: any) => ({
        id: item.id_pembelian_item,
        id_produk: item.id_produk,
        qty: item.qty,
        harga: item.harga,
    })) as ItemRow[],
})

const errors = ref<Record<string, string>>({})

// Calculate totals
const total = computed(() => {
    return form.value.items.reduce((sum, item) => {
        return sum + (item.qty * item.harga)
    }, 0)
})

// Add item row
function addItem() {
    form.value.items.push({
        id_produk: null,
        qty: 1,
        harga: 0,
    })
}

// Remove item row
function removeItem(index: number) {
    form.value.items.splice(index, 1)
}

// Auto-fill harga when product selected
function onProductChange(item: ItemRow) {
    if (item.id_produk) {
        const produk = produks.value.find((p: any) => p.id === item.id_produk)
        if (produk) {
            item.harga = produk.harga_beli || 0
        }
    }
}

function formatCurrency(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
}

// Submit form
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
    
    router.put(`/app/admin/transactions/pembelian/${pembelian.value.id_pembelian}`, {
        tanggal: form.value.tanggal,
        id_supplier: form.value.id_supplier,
        id_karyawan: form.value.id_karyawan,
        nota_supplier: form.value.nota_supplier,
        catatan: form.value.catatan,
        tipe_pembelian: form.value.tipe_pembelian,
        jenis_pembayaran: form.value.jenis_pembayaran,
        tgl_tempo: form.value.tgl_tempo || null,
        items: form.value.items.map(item => ({
            id: item.id,
            id_produk: item.id_produk,
            qty: item.qty,
            harga: item.harga,
        })),
    }, {
        onError: (err) => {
            errors.value = err
        },
    })
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="Edit Purchase"
                :description="`Edit purchase #${pembelian.no_po}`"
                :breadcrumbs="[
                    { label: 'Transactions', href: '/app/admin/transactions' },
                    { label: 'Pembelian', href: '/app/admin/transactions/pembelian' },
                    { label: 'Edit Purchase' }
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
                                            <th class="px-3 py-2 text-left text-sm font-medium text-muted-foreground w-1/2">Product</th>
                                            <th class="px-3 py-2 text-center text-sm font-medium text-muted-foreground w-20">Qty</th>
                                            <th class="px-3 py-2 text-right text-sm font-medium text-muted-foreground w-32">Price</th>
                                            <th class="px-3 py-2 text-right text-sm font-medium text-muted-foreground w-32">Subtotal</th>
                                            <th class="px-3 py-2 w-12"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr v-for="(item, index) in form.items" :key="index">
                                            <td class="px-3 py-2">
                                                <select
                                                    v-model="item.id_produk"
                                                    class="w-full h-9 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                    @change="onProductChange(item)"
                                                >
                                                    <option :value="null">Select product...</option>
                                                    <option
                                                        v-for="produk in produks"
                                                        :key="produk.id"
                                                        :value="produk.id"
                                                    >
                                                        {{ produk.nama_produk }} - {{ produk.sku }}
                                                    </option>
                                                </select>
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
                                                    v-model.number="item.harga"
                                                    type="number"
                                                    min="0"
                                                    class="text-right"
                                                />
                                            </td>
                                            <td class="px-3 py-2 text-right font-medium">
                                                {{ formatCurrency(item.qty * item.harga) }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 text-destructive hover:text-destructive"
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
                                    <label class="text-sm text-muted-foreground block mb-1">Supplier</label>
                                    <select
                                        v-model="form.id_supplier"
                                        class="w-full h-9 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option :value="null">Select supplier...</option>
                                        <option
                                            v-for="supplier in suppliers"
                                            :key="supplier.id"
                                            :value="supplier.id"
                                        >
                                            {{ supplier.nama_supplier }} ({{ supplier.kode_supplier }})
                                        </option>
                                    </select>
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
                                
                                <div v-if="form.jenis_pembayaran === 'kredit'">
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
                                    <span class="text-muted-foreground">Total</span>
                                    <span class="text-lg font-bold">{{ formatCurrency(total) }}</span>
                                </div>
                            </div>
                            
                            <Button
                                type="submit"
                                class="w-full mt-6"
                                size="lg"
                            >
                                <Save class="h-4 w-4 mr-2" />
                                Update Purchase
                            </Button>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
