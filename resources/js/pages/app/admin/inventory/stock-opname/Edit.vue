<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import FormField from '@/components/forms/FormField.vue'
import { ArrowLeft, Plus, Trash2, PackageSearch, Save } from 'lucide-vue-next'

interface Gudang { id: number; nama_gudang: string }

interface Batch {
    id: number
    qty_sisa: number
    hpp: number
    kondisi: string | null
    no_po: string | null
}

interface ProdukWithBatches {
    id: number
    nama_produk: string
    sku: string
    batches: Batch[]
}

interface ExistingItem {
    id: number
    produk_id: number
    pembelian_item_id: number | null
    stok_sistem: number
    stok_fisik: number
    selisih: number
    catatan: string | null
}

interface Opname {
    id: number
    kode: string
    tanggal: string
    gudang_id: number | null
    catatan: string | null
    items: ExistingItem[]
}

interface PageProps {
    opname: Opname
    gudangs: Gudang[]
    produks: ProdukWithBatches[]
}

const page = usePage<PageProps>()
const opname = computed(() => page.props.opname)
const gudangs = computed(() => page.props.gudangs || [])
const produks = computed(() => page.props.produks || [])

interface ItemRow {
    _key: string
    produk_id: number | null
    pembelian_item_id: number | null
    stok_sistem: number
    stok_fisik: number
    catatan: string
}

const form = ref({
    tanggal: opname.value.tanggal,
    gudang_id: opname.value.gudang_id,
    catatan: opname.value.catatan || '',
})

const items = ref<ItemRow[]>(
    opname.value.items.map(i => ({
        _key: `existing-${i.id}`,
        produk_id: i.produk_id,
        pembelian_item_id: i.pembelian_item_id,
        stok_sistem: i.stok_sistem,
        stok_fisik: i.stok_fisik,
        catatan: i.catatan || '',
    }))
)

const errors = ref<Record<string, string>>({})
const isSubmitting = ref(false)

function addItem() {
    items.value.push({
        _key: `item-${Date.now()}-${Math.random()}`,
        produk_id: null,
        pembelian_item_id: null,
        stok_sistem: 0,
        stok_fisik: 0,
        catatan: '',
    })
}

function removeItem(index: number) {
    items.value.splice(index, 1)
}

function getBatches(produkId: number | null): Batch[] {
    if (!produkId) return []
    return produks.value.find(p => p.id === produkId)?.batches || []
}

function onBatchSelect(item: ItemRow, batchId: number | null) {
    item.pembelian_item_id = batchId
    if (batchId) {
        const p = produks.value.find(p => p.id === item.produk_id)
        const batch = p?.batches.find(b => b.id === batchId)
        item.stok_sistem = batch?.qty_sisa ?? 0
    } else {
        item.stok_sistem = 0
    }
}

function selisih(item: ItemRow): number {
    return item.stok_fisik - item.stok_sistem
}

function selisihClass(item: ItemRow): string {
    const s = selisih(item)
    if (s > 0) return 'text-green-600'
    if (s < 0) return 'text-red-600'
    return 'text-muted-foreground'
}

function submit() {
    errors.value = {}

    if (items.value.length === 0) {
        errors.value.items = 'Please add at least one item.'
        return
    }

    const hasInvalid = items.value.some(i => !i.produk_id)
    if (hasInvalid) {
        errors.value.items = 'Each item must have a product selected.'
        return
    }

    isSubmitting.value = true

    router.put(`/app/admin/inventory/stock-opname/${opname.value.id}`, {
        ...form.value,
        items: items.value.map(i => ({
            produk_id: i.produk_id,
            pembelian_item_id: i.pembelian_item_id || null,
            stok_fisik: i.stok_fisik,
            catatan: i.catatan,
        })),
    }, {
        onError: (err) => {
            errors.value = err
            isSubmitting.value = false
        },
        onFinish: () => { isSubmitting.value = false },
    })
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                :title="`Edit: ${opname.kode}`"
                description="Update stock opname count data."
                :breadcrumbs="[
                    { label: 'Inventory', href: '/app/admin/inventory/stock-opname' },
                    { label: 'Stock Opname', href: '/app/admin/inventory/stock-opname' },
                    { label: opname.kode, href: `/app/admin/inventory/stock-opname/${opname.id}` },
                    { label: 'Edit' },
                ]"
            >
                <template #actions>
                    <Button variant="outline" @click="router.visit(`/app/admin/inventory/stock-opname/${opname.id}`)">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Cancel
                    </Button>
                </template>
            </PageHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Left: Items -->
                    <div class="lg:col-span-2 space-y-6">
                        <Card class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-semibold flex items-center gap-2">
                                    <PackageSearch class="h-5 w-5" />
                                    Count Items
                                </h3>
                                <Button type="button" variant="outline" size="sm" @click="addItem">
                                    <Plus class="h-4 w-4 mr-1" />
                                    Add Item
                                </Button>
                            </div>

                            <p v-if="errors.items" class="text-sm text-destructive mb-4">{{ errors.items }}</p>

                            <div v-if="items.length === 0" class="flex flex-col items-center justify-center py-12 text-muted-foreground">
                                <PackageSearch class="h-12 w-12 mb-3 opacity-30" />
                                <p class="text-sm">No items yet.</p>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="(item, index) in items"
                                    :key="item._key"
                                    class="rounded-lg border p-4 space-y-3"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-muted-foreground">Item #{{ index + 1 }}</span>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8 text-destructive hover:text-destructive"
                                            @click="removeItem(index)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <FormField label="Product" :name="`items.${index}.produk_id`" required>
                                            <select
                                                v-model="item.produk_id"
                                                class="w-full h-9 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                @change="item.pembelian_item_id = null; item.stok_sistem = 0"
                                            >
                                                <option :value="null">Select product...</option>
                                                <option v-for="p in produks" :key="p.id" :value="p.id">
                                                    {{ p.nama_produk }} — {{ p.sku }}
                                                </option>
                                            </select>
                                        </FormField>

                                        <FormField label="Batch" :name="`items.${index}.pembelian_item_id`">
                                            <select
                                                :value="item.pembelian_item_id"
                                                class="w-full h-9 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                :disabled="!item.produk_id"
                                                @change="onBatchSelect(item, ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null)"
                                            >
                                                <option :value="null">Any / All batches</option>
                                                <option v-for="b in getBatches(item.produk_id)" :key="b.id" :value="b.id">
                                                    {{ b.no_po || `Batch #${b.id}` }} — Sisa: {{ b.qty_sisa }}
                                                </option>
                                            </select>
                                        </FormField>
                                    </div>

                                    <div class="grid grid-cols-3 gap-3">
                                        <FormField label="System Stock" :name="`items.${index}.stok_sistem`">
                                            <Input :model-value="item.stok_sistem" type="number" disabled class="bg-muted" />
                                        </FormField>

                                        <FormField label="Physical Count" :name="`items.${index}.stok_fisik`" required>
                                            <Input v-model.number="item.stok_fisik" type="number" min="0" />
                                        </FormField>

                                        <div>
                                            <label class="text-sm font-medium leading-none mb-2 block">Variance</label>
                                            <div
                                                class="flex h-9 items-center rounded-md border border-input px-3 text-sm font-semibold"
                                                :class="selisihClass(item)"
                                            >
                                                {{ selisih(item) >= 0 ? '+' : '' }}{{ selisih(item) }}
                                            </div>
                                        </div>
                                    </div>

                                    <FormField label="Note" :name="`items.${index}.catatan`">
                                        <Input v-model="item.catatan" placeholder="Optional note..." />
                                    </FormField>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Right: Details -->
                    <div class="space-y-6">
                        <Card class="p-6">
                            <h3 class="font-semibold mb-4">Opname Details</h3>

                            <div class="space-y-4">
                                <FormField label="Date" name="tanggal" required>
                                    <Input v-model="form.tanggal" type="date" />
                                </FormField>

                                <FormField label="Warehouse" name="gudang_id">
                                    <select
                                        v-model="form.gudang_id"
                                        class="w-full h-9 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option :value="null">No specific warehouse</option>
                                        <option v-for="g in gudangs" :key="g.id" :value="g.id">
                                            {{ g.nama_gudang }}
                                        </option>
                                    </select>
                                </FormField>

                                <FormField label="Notes" name="catatan">
                                    <textarea
                                        v-model="form.catatan"
                                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                        placeholder="Opname notes..."
                                    />
                                </FormField>
                            </div>

                            <Button type="submit" class="w-full mt-6" size="lg" :disabled="isSubmitting">
                                <Save class="h-4 w-4 mr-2" />
                                Update Opname
                            </Button>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
