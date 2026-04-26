<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import { ArrowLeft, Plus, Trash2, ShoppingCart, Save, CreditCard, Banknote } from 'lucide-vue-next'

const page = usePage()

const members = computed(() => page.props.members || [])
const karyawans = computed(() => page.props.karyawans || [])
const gudangs = computed(() => page.props.gudangs || [])
const produks = computed(() => page.props.produks || [])
const paymentAccounts = computed(() => page.props.paymentAccounts || [])
const metodeBayarOptions = computed(() => page.props.metodeBayarOptions || [])

interface ItemRow {
    id: string
    id_produk: number | null
    qty: number
    harga_jual: number
}

interface PaymentRow {
    id: string
    metode_bayar: string
    akun_transaksi_id: number | null
    jumlah: number
}

const form = ref({
    tanggal_penjualan: new Date().toISOString().split('T')[0],
    id_member: null as number | null,
    id_karyawan: null as number | null,
    gudang_id: null as number | null,
    catatan: '',
    diskon_total: 0,
    items: [] as ItemRow[],
    pembayarans: [] as PaymentRow[],
})

const errors = ref<Record<string, string>>({})

// Calculate totals
const itemsSubtotal = computed(() => {
    return form.value.items.reduce((sum, item) => {
        return sum + (item.qty * item.harga_jual)
    }, 0)
})

const grandTotal = computed(() => {
    return itemsSubtotal.value - (form.value.diskon_total || 0)
})

const totalPayment = computed(() => {
    return form.value.pembayarans.reduce((sum, p) => sum + (p.jumlah || 0), 0)
})

const remainingAmount = computed(() => {
    return grandTotal.value - totalPayment.value
})

const changeAmount = computed(() => {
    return Math.max(0, totalPayment.value - grandTotal.value)
})

const isFullyPaid = computed(() => {
    return totalPayment.value >= grandTotal.value && grandTotal.value > 0
})

// Add item row
function addItem() {
    form.value.items.push({
        id: `new-${Date.now()}`,
        id_produk: null,
        qty: 1,
        harga_jual: 0,
    })
}

// Remove item row
function removeItem(index: number) {
    form.value.items.splice(index, 1)
}

// Auto-fill harga when product selected — harga diisi manual karena tidak ada di data produk
function onProductChange(item: ItemRow) {
    if (!item.id_produk) {
        item.harga_jual = 0
    }
}

// Add payment row
function addPayment() {
    form.value.pembayarans.push({
        id: `new-${Date.now()}`,
        metode_bayar: 'cash',
        akun_transaksi_id: null,
        jumlah: remainingAmount.value > 0 ? remainingAmount.value : 0,
    })
}

// Remove payment row
function removePayment(index: number) {
    form.value.pembayarans.splice(index, 1)
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
    
    router.post('/app/admin/transactions/penjualan', {
        tanggal_penjualan: form.value.tanggal_penjualan,
        id_member: form.value.id_member,
        id_karyawan: form.value.id_karyawan,
        gudang_id: form.value.gudang_id,
        catatan: form.value.catatan,
        diskon_total: form.value.diskon_total,
        items: form.value.items.map(item => ({
            id_produk: item.id_produk,
            qty: item.qty,
            harga_jual: item.harga_jual,
        })),
        pembayarans: form.value.pembayarans.map(p => ({
            metode_bayar: p.metode_bayar,
            akun_transaksi_id: p.akun_transaksi_id,
            jumlah: p.jumlah,
        })),
    }, {
        onError: (err) => {
            errors.value = err
        },
    })
}

// Auto-add initial payment row
function initPayments() {
    if (form.value.pembayarans.length === 0 && grandTotal.value > 0) {
        addPayment()
    }
}

// Watch grandTotal and add payment when ready
watch(grandTotal, (newVal) => {
    if (newVal > 0 && form.value.pembayarans.length === 0) {
        initPayments()
    }
})

// Initialize with one empty item
if (form.value.items.length === 0) {
    addItem()
}

// Initialize payment if grandTotal > 0
if (grandTotal.value > 0) {
    initPayments()
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="New Transaction"
                description="Create a new sales transaction."
                :breadcrumbs="[
                    { label: 'Transactions', href: '/app/admin/transactions/penjualan' },
                    { label: 'New Transaction' }
                ]"
            >
                <template #actions>
                    <Link href="/app/admin/transactions/penjualan">
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
                                    <ShoppingCart class="h-5 w-5" />
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
                                        <tr v-for="(item, index) in form.items" :key="item.id">
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
                                                    v-model.number="item.harga_jual"
                                                    type="number"
                                                    min="0"
                                                    class="text-right"
                                                />
                                            </td>
                                            <td class="px-3 py-2 text-right font-medium">
                                                {{ formatCurrency(item.qty * item.harga_jual) }}
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
                        
                        <!-- Payments -->
                        <Card class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-semibold flex items-center gap-2">
                                    <CreditCard class="h-5 w-5" />
                                    Payment
                                </h3>
                                <Button type="button" variant="outline" size="sm" @click="addPayment">
                                    <Plus class="h-4 w-4 mr-1" />
                                    Add Payment
                                </Button>
                            </div>
                            
                            <div class="space-y-3">
                                <div v-for="(payment, index) in form.pembayarans" :key="payment.id" class="flex gap-3 items-start">
                                    <div class="flex-1">
                                        <select
                                            v-model="payment.metode_bayar"
                                            class="w-full h-9 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        >
                                            <option
                                                v-for="option in metodeBayarOptions"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="flex-1" v-if="payment.metode_bayar !== 'cash'">
                                        <select
                                            v-model="payment.akun_transaksi_id"
                                            class="w-full h-9 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        >
                                            <option :value="null">Select account...</option>
                                            <option
                                                v-for="account in paymentAccounts"
                                                :key="account.id"
                                                :value="account.id"
                                            >
                                                {{ account.nama_akun }} ({{ account.jenis }})
                                            </option>
                                        </select>
                                    </div>
                                    <div class="flex-1">
                                        <Input
                                            v-model.number="payment.jumlah"
                                            type="number"
                                            min="0"
                                            placeholder="Amount"
                                            class="text-right"
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="h-9 w-9 text-destructive hover:text-destructive"
                                        @click="removePayment(index)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                                
                                <div v-if="form.pembayarans.length === 0" class="text-center py-4 text-muted-foreground text-sm">
                                    No payment added. Click "Add Payment" to add payment.
                                </div>
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
                    
                    <!-- Right: Summary + Customer -->
                    <div class="space-y-6">
                        <!-- Transaction Details -->
                        <Card class="p-6">
                            <h3 class="font-semibold mb-4">Transaction Details</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm text-muted-foreground block mb-1">Date</label>
                                    <Input
                                        v-model="form.tanggal_penjualan"
                                        type="date"
                                        class="w-full"
                                    />
                                </div>
                                
                                <div>
                                    <label class="text-sm text-muted-foreground block mb-1">Customer</label>
                                    <select
                                        v-model="form.id_member"
                                        class="w-full h-9 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option :value="null">Guest</option>
                                        <option
                                            v-for="member in members"
                                            :key="member.id"
                                            :value="member.id"
                                        >
                                            {{ member.nama_member }} ({{ member.kode_member }})
                                        </option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="text-sm text-muted-foreground block mb-1">Warehouse</label>
                                    <select
                                        v-model="form.gudang_id"
                                        class="w-full h-9 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option :value="null">Select warehouse...</option>
                                        <option
                                            v-for="gudang in gudangs"
                                            :key="gudang.id"
                                            :value="gudang.id"
                                        >
                                            {{ gudang.nama_gudang }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </Card>
                        
                        <!-- Summary -->
                        <Card class="p-6">
                            <h3 class="font-semibold mb-4">Summary</h3>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Subtotal</span>
                                    <span>{{ formatCurrency(itemsSubtotal) }}</span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-muted-foreground">Discount</span>
                                    <Input
                                        v-model.number="form.diskon_total"
                                        type="number"
                                        min="0"
                                        class="w-32 text-right h-8"
                                    />
                                </div>
                                
                                <div class="flex justify-between text-lg font-bold pt-3 border-t">
                                    <span>Grand Total</span>
                                    <span class="text-primary">{{ formatCurrency(grandTotal) }}</span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Total Payment</span>
                                    <span :class="isFullyPaid ? 'text-green-600' : 'text-muted-foreground'">
                                        {{ formatCurrency(totalPayment) }}
                                    </span>
                                </div>
                                
                                <div v-if="remainingAmount > 0" class="flex justify-between text-red-600">
                                    <span>Remaining</span>
                                    <span class="font-medium">{{ formatCurrency(remainingAmount) }}</span>
                                </div>
                                
                                <div v-if="changeAmount > 0" class="flex justify-between text-green-600">
                                    <span>Change</span>
                                    <span class="font-medium">{{ formatCurrency(changeAmount) }}</span>
                                </div>
                                
                                <div v-if="isFullyPaid" class="pt-2">
                                    <Badge variant="success" class="w-full justify-center py-1">
                                        Fully Paid
                                    </Badge>
                                </div>
                            </div>
                            
                            <Button
                                type="submit"
                                class="w-full mt-6"
                                size="lg"
                            >
                                <Save class="h-4 w-4 mr-2" />
                                Save Transaction
                            </Button>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
