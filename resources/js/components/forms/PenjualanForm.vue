<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { router, usePage, useForm } from '@inertiajs/vue3'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import Dialog from '@/components/ui/dialog.vue'
import RelationSelect, { type SelectOption } from '@/components/forms/RelationSelect.vue'
import FormField from '@/components/forms/FormField.vue'
import { Plus, Trash2, ShoppingCart, Save, CreditCard, UserPlus } from 'lucide-vue-next'
import ProdukSelect, { type ProdukOption } from '@/components/forms/ProdukSelect.vue'

const emit = defineEmits<{
    saved: []
}>()

const page = usePage()

const members = computed(() => page.props.members || [])
const karyawans = computed(() => page.props.karyawans || [])
const gudangs = computed(() => page.props.gudangs || [])
const produks = computed(() => page.props.produks || [])
const paymentAccounts = computed(() => page.props.paymentAccounts || [])
const metodeBayarOptions = computed(() => page.props.metodeBayarOptions || [])

const memberOptions = computed<SelectOption[]>(() =>
    members.value.map((m: any) => ({
        label: `${m.nama_member} (${m.kode_member})`,
        value: m.id,
    }))
)

const showCreateMemberDialog = ref(false)
const memberForm = useForm({
    nama_member: '',
    email: '',
    no_hp: '',
    alamat: '',
    provinsi: '',
    kota: '',
    kecamatan: '',
    image_url: '',
})

function openCreateMemberDialog(name: string) {
    memberForm.reset()
    memberForm.clearErrors()
    memberForm.nama_member = name
    showCreateMemberDialog.value = true
}

function closeCreateMemberDialog() {
    showCreateMemberDialog.value = false
    memberForm.reset()
    memberForm.clearErrors()
}

function handleCreateMember() {
    if (!memberForm.nama_member.trim() || !memberForm.no_hp.trim()) return

    memberForm.post('/app/admin/master-data/member', {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeCreateMemberDialog()
        },
    })
}

interface ItemRow {
    id: string
    id_produk: number | null
    qty: number
    selling_price: number
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

const itemsSubtotal = computed(() => {
    return form.value.items.reduce((sum, item) => {
        return sum + (item.qty * item.selling_price)
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

function addItem() {
    form.value.items.push({
        id: `new-${Date.now()}`,
        id_produk: null,
        qty: 1,
        selling_price: 0,
    })
}

function removeItem(index: number) {
    form.value.items.splice(index, 1)
}

function onProductSelect(item: ItemRow, produk: ProdukOption) {
    item.id_produk = produk.id
}

function addPayment() {
    form.value.pembayarans.push({
        id: `new-${Date.now()}`,
        metode_bayar: 'cash',
        akun_transaksi_id: null,
        jumlah: remainingAmount.value > 0 ? remainingAmount.value : 0,
    })
}

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
            selling_price: item.selling_price,
        })),
        pembayarans: form.value.pembayarans.map(p => ({
            metode_bayar: p.metode_bayar,
            akun_transaksi_id: p.akun_transaksi_id,
            jumlah: p.jumlah,
        })),
    }, {
        onSuccess: () => {
            resetForm()
            emit('saved')
        },
        onError: (err) => {
            errors.value = err
        },
    })
}

function resetForm() {
    form.value = {
        tanggal_penjualan: new Date().toISOString().split('T')[0],
        id_member: null,
        id_karyawan: null,
        gudang_id: null,
        catatan: '',
        diskon_total: 0,
        items: [],
        pembayarans: [],
    }
    errors.value = {}
    addItem()
}

function initPayments() {
    if (form.value.pembayarans.length === 0 && grandTotal.value > 0) {
        addPayment()
    }
}

watch(grandTotal, (newVal) => {
    if (newVal > 0 && form.value.pembayarans.length === 0) {
        initPayments()
    }
})

onMounted(() => {
    if (form.value.items.length === 0) {
        addItem()
    }
    if (grandTotal.value > 0) {
        initPayments()
    }
})
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="space-y-6">
            <!-- Transaction Details -->
            <Card class="p-4">
                <h3 class="font-semibold mb-4">Transaction Details</h3>
                
                <div class="space-y-3">
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
                        <RelationSelect
                            v-model="form.id_member"
                            :options="memberOptions"
                            placeholder="Guest"
                            search-placeholder="Search customer..."
                            empty-message="No customers found"
                            icon="building"
                            :clearable="true"
                            :enable-create="true"
                            create-label="Create customer"
                            @create="openCreateMemberDialog"
                        />
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
            
            <!-- Items -->
            <Card class="p-4">
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
                
                <div class="space-y-3">
                    <div v-for="(item, index) in form.items" :key="item.id" class="flex gap-2 items-start">
                        <div class="flex-1">
                            <ProdukSelect
                                :model-value="item.id_produk"
                                @update:model-value="item.id_produk = $event"
                                @select="onProductSelect(item, $event)"
                                placeholder="Search product..."
                                :in-stock-only="true"
                            />
                        </div>
                        <div class="w-16">
                            <Input
                                v-model.number="item.qty"
                                type="number"
                                min="1"
                                class="text-center"
                            />
                        </div>
                        <div class="w-28">
                            <Input
                                v-model.number="item.selling_price"
                                type="number"
                                min="0"
                                class="text-right"
                            />
                        </div>
                        <div class="w-24 text-right text-sm font-medium py-2">
                            {{ formatCurrency(item.qty * item.selling_price) }}
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="h-9 w-9 text-destructive hover:text-destructive shrink-0"
                            :disabled="form.items.length <= 1"
                            @click="removeItem(index)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </Card>
            
            <!-- Payments -->
            <Card class="p-4">
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
                    <div v-for="(payment, index) in form.pembayarans" :key="payment.id" class="flex gap-2 items-start">
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
                        <div class="w-28">
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
                            class="h-9 w-9 text-destructive hover:text-destructive shrink-0"
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
            <Card class="p-4">
                <h3 class="font-semibold mb-3">Notes</h3>
                <textarea
                    v-model="form.catatan"
                    class="w-full h-20 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    placeholder="Add notes..."
                />
            </Card>
            
            <!-- Summary -->
            <Card class="p-4">
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

        <Dialog v-model:open="showCreateMemberDialog" class="max-w-lg">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Create New Customer</h2>

                <form @submit.prevent="handleCreateMember" class="space-y-4">
                    <FormField
                        label="Nama Member"
                        name="nama_member"
                        :error="memberForm.errors.nama_member"
                        required
                    >
                        <Input
                            v-model="memberForm.nama_member"
                            placeholder="Enter member name"
                        />
                    </FormField>

                    <div class="grid grid-cols-2 gap-4">
                        <FormField
                            label="No. HP"
                            name="no_hp"
                            :error="memberForm.errors.no_hp"
                            required
                        >
                            <Input v-model="memberForm.no_hp" placeholder="08xxxxxxxxxx" />
                        </FormField>
                        <FormField label="Email" name="email" :error="memberForm.errors.email">
                            <Input
                                v-model="memberForm.email"
                                type="email"
                                placeholder="email@example.com"
                            />
                        </FormField>
                    </div>

                    <FormField label="Alamat" name="alamat" :error="memberForm.errors.alamat">
                        <textarea
                            v-model="memberForm.alamat"
                            placeholder="Enter address"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            rows="3"
                        ></textarea>
                    </FormField>

                    <div class="grid grid-cols-3 gap-4">
                        <FormField
                            label="Provinsi"
                            name="provinsi"
                            :error="memberForm.errors.provinsi"
                        >
                            <Input v-model="memberForm.provinsi" placeholder="Provinsi" />
                        </FormField>
                        <FormField label="Kota" name="kota" :error="memberForm.errors.kota">
                            <Input v-model="memberForm.kota" placeholder="Kota" />
                        </FormField>
                        <FormField
                            label="Kecamatan"
                            name="kecamatan"
                            :error="memberForm.errors.kecamatan"
                        >
                            <Input v-model="memberForm.kecamatan" placeholder="Kecamatan" />
                        </FormField>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" @click="closeCreateMemberDialog">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="memberForm.processing">
                            <UserPlus class="h-4 w-4 mr-2" />
                            Create Customer
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog>
    </form>
</template>
