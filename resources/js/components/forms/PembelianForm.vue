<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import RelationSelect, { type SelectOption } from '@/components/forms/RelationSelect.vue'
import ProdukSelect, { type ProdukOption } from '@/components/forms/ProdukSelect.vue'
import { Plus, Trash2, Package, Save } from 'lucide-vue-next'

const emit = defineEmits<{
    saved: []
}>()

const page = usePage()

const suppliers = computed(() => (page.props as any).suppliers || [])
const jenisPembayaranOptions = computed(() => (page.props as any).jenisPembayaranOptions || [])

const supplierOptions = computed<SelectOption[]>(() =>
    (suppliers.value as any[]).map((s: any) => ({
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

const totalMargin = computed(() => {
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
        tanggal: new Date().toISOString().split('T')[0],
        id_supplier: null,
        id_karyawan: null,
        nota_supplier: '',
        catatan: '',
        tipe_pembelian: 'non_ppn',
        jenis_pembayaran: 'lunas',
        tgl_tempo: '',
        items: [],
    }
    errors.value = {}
    addItem()
}

onMounted(() => {
    if (form.value.items.length === 0) {
        addItem()
    }
})
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="space-y-6">
            <Card class="p-4">
                <h3 class="font-semibold mb-4">Purchase Details</h3>

                <div class="space-y-3">
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
                        <RelationSelect
                            v-model="form.id_supplier"
                            :options="supplierOptions"
                            placeholder="Select supplier..."
                            search-placeholder="Search supplier..."
                            empty-message="No suppliers found"
                            icon="building"
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

            <Card class="p-4">
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

                <div class="space-y-3">
                    <div class="flex gap-2 items-center text-xs text-muted-foreground font-medium pb-1">
                        <div class="flex-1">Product</div>
                        <div class="w-16 text-center">Qty</div>
                        <div class="w-28 text-right">Cost</div>
                        <div class="w-28 text-right">Price</div>
                        <div class="w-24 text-right">Subtotal</div>
                        <div class="w-9"></div>
                    </div>
                    <div v-for="(item, index) in form.items" :key="item.id" class="flex gap-2 items-start">
                        <div class="flex-1">
                            <ProdukSelect
                                :model-value="item.id_produk"
                                @update:model-value="item.id_produk = $event"
                                @select="onProductSelect(item, $event)"
                                placeholder="Search product..."
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
                                v-model.number="item.cost_price"
                                type="number"
                                min="0"
                                class="text-right"
                                placeholder="0"
                            />
                        </div>
                        <div class="w-28">
                            <Input
                                v-model.number="item.selling_price"
                                type="number"
                                min="0"
                                class="text-right"
                                placeholder="0"
                            />
                        </div>
                        <div class="w-24 text-right text-sm font-medium py-2">
                            {{ formatCurrency(item.qty * item.cost_price) }}
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

            <Card class="p-4">
                <h3 class="font-semibold mb-3">Notes</h3>
                <textarea
                    v-model="form.catatan"
                    class="w-full h-20 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    placeholder="Add notes..."
                />
            </Card>

            <Card class="p-4">
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
                        <span :class="totalMargin >= 0 ? 'text-green-600' : 'text-red-600'">{{ formatCurrency(totalMargin) }}</span>
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
    </form>
</template>