<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import FormField from '@/components/forms/FormField.vue'
import { ArrowLeft, Plus, Trash2, ClipboardList, Save } from 'lucide-vue-next'

interface Gudang { id: number; nama_gudang: string }
interface Produk { id: number; nama_produk: string; sku: string; brand?: { id: number; nama_brand: string } | null }

interface PageProps {
    gudangs: Gudang[]
    produks: Produk[]
}

const page = usePage<PageProps>()
const gudangs = computed(() => page.props.gudangs || [])
const produks = computed(() => page.props.produks || [])

interface ItemRow {
    _key: string
    produk_id: number | null
    pembelian_item_id: number | null
    qty: number
    keterangan: string
}

const form = ref({
    tanggal: new Date().toISOString().split('T')[0],
    gudang_id: null as number | null,
    catatan: '',
})

const items = ref<ItemRow[]>([])
const errors = ref<Record<string, string>>({})
const isSubmitting = ref(false)

function addItem() {
    items.value.push({
        _key: `item-${Date.now()}-${Math.random()}`,
        produk_id: null,
        pembelian_item_id: null,
        qty: 0,
        keterangan: '',
    })
}

function removeItem(index: number) {
    items.value.splice(index, 1)
}

function getProdukName(id: number | null): string {
    if (!id) return ''
    const p = produks.value.find(p => p.id === id)
    return p ? `${p.nama_produk} (${p.sku})` : String(id)
}

function submit() {
    errors.value = {}

    if (items.value.length === 0) {
        errors.value.items = 'Please add at least one item.'
        return
    }

    const hasInvalid = items.value.some(i => !i.produk_id || i.qty === 0)
    if (hasInvalid) {
        errors.value.items = 'Each item must have a product and a non-zero quantity (positive = add, negative = subtract).'
        return
    }

    isSubmitting.value = true

    router.post('/app/admin/inventory/stock-adjustment', {
        ...form.value,
        items: items.value.map(i => ({
            produk_id: i.produk_id,
            pembelian_item_id: i.pembelian_item_id || null,
            qty: i.qty,
            keterangan: i.keterangan,
        })),
    }, {
        onError: (err) => {
            errors.value = err
            isSubmitting.value = false
        },
        onFinish: () => { isSubmitting.value = false },
    })
}

// Start with one empty item
addItem()
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="New Stock Adjustment"
                description="Create a stock adjustment to correct inventory discrepancies."
                :breadcrumbs="[
                    { label: 'Inventory', href: '/app/admin/inventory/stock-adjustment' },
                    { label: 'Stock Adjustment', href: '/app/admin/inventory/stock-adjustment' },
                    { label: 'New Adjustment' },
                ]"
            >
                <template #actions>
                    <Button variant="outline" @click="router.visit('/app/admin/inventory/stock-adjustment')">
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
                                    <ClipboardList class="h-5 w-5" />
                                    Adjustment Items
                                </h3>
                                <Button type="button" variant="outline" size="sm" @click="addItem">
                                    <Plus class="h-4 w-4 mr-1" />
                                    Add Item
                                </Button>
                            </div>

                            <p v-if="errors.items" class="text-sm text-destructive mb-4">
                                {{ errors.items }}
                            </p>

                            <div v-if="items.length === 0" class="flex flex-col items-center justify-center py-12 text-muted-foreground">
                                <ClipboardList class="h-12 w-12 mb-3 opacity-30" />
                                <p class="text-sm">No items yet. Click "Add Item" to start.</p>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="(item, index) in items"
                                    :key="item._key"
                                    class="rounded-lg border p-4 space-y-3"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-muted-foreground">
                                            Item #{{ index + 1 }}
                                        </span>
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
                                            >
                                                <option :value="null">Select product...</option>
                                                <option
                                                    v-for="p in produks"
                                                    :key="p.id"
                                                    :value="p.id"
                                                >
                                                    {{ p.nama_produk }} — {{ p.sku }}
                                                    <template v-if="p.brand"> ({{ p.brand.nama_brand }})</template>
                                                </option>
                                            </select>
                                            <p v-if="errors[`items.${index}.produk_id`]" class="text-sm text-destructive mt-1">
                                                {{ errors[`items.${index}.produk_id`] }}
                                            </p>
                                        </FormField>

                                        <FormField label="Qty (+ add / − subtract)" :name="`items.${index}.qty`" required>
                                            <Input
                                                v-model.number="item.qty"
                                                type="number"
                                                placeholder="e.g. 5 or -3"
                                            />
                                            <p v-if="errors[`items.${index}.qty`]" class="text-sm text-destructive mt-1">
                                                {{ errors[`items.${index}.qty`] }}
                                            </p>
                                        </FormField>
                                    </div>

                                    <FormField label="Note" :name="`items.${index}.keterangan`">
                                        <Input
                                            v-model="item.keterangan"
                                            placeholder="Optional note for this item..."
                                        />
                                    </FormField>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Right: Details -->
                    <div class="space-y-6">
                        <Card class="p-6">
                            <h3 class="font-semibold mb-4">Adjustment Details</h3>

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
                                        <option
                                            v-for="g in gudangs"
                                            :key="g.id"
                                            :value="g.id"
                                        >
                                            {{ g.nama_gudang }}
                                        </option>
                                    </select>
                                </FormField>

                                <FormField label="Notes" name="catatan">
                                    <textarea
                                        v-model="form.catatan"
                                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                        placeholder="Reason for adjustment..."
                                    />
                                </FormField>

                                <div class="pt-2 border-t">
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground mb-3">
                                        <Badge variant="secondary">{{ items.length }} items</Badge>
                                        will be saved as
                                        <Badge variant="warning">Draft</Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground mb-4">
                                        After saving as draft, review and then <strong>Post</strong> to apply stock changes.
                                    </p>
                                </div>
                            </div>

                            <Button
                                type="submit"
                                class="w-full"
                                size="lg"
                                :disabled="isSubmitting"
                            >
                                <Save class="h-4 w-4 mr-2" />
                                Save Adjustment
                            </Button>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
