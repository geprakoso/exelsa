<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import { ArrowLeft, CheckCircle2, ClipboardList, Calendar, MapPin, User2, FileText } from 'lucide-vue-next'

interface Gudang { id: number; nama_gudang: string }
interface UserInfo { id: number; name: string }
interface Produk { id: number; nama_produk: string; sku: string }

interface AdjustmentItem {
    id: number
    produk_id: number
    produk: Produk
    qty: number
    keterangan: string | null
}

interface Adjustment {
    id: number
    kode: string
    tanggal: string
    status: 'draft' | 'posted'
    gudang: Gudang | null
    user: UserInfo | null
    posted_by: UserInfo | null
    posted_at: string | null
    catatan: string | null
    items: AdjustmentItem[]
    created_at: string
}

interface PageProps {
    adjustment: Adjustment
}

const page = usePage<PageProps>()
const adjustment = computed(() => page.props.adjustment)

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'long', year: 'numeric'
    })
}

function postAdjustment() {
    if (!confirm(`Post adjustment ${adjustment.value.kode}? This cannot be undone.`)) return
    router.post(`/app/admin/inventory/stock-adjustment/${adjustment.value.id}/post`)
}

function statusVariant(status: string): 'success' | 'warning' {
    return status === 'posted' ? 'success' : 'warning'
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                :title="adjustment.kode"
                description="Stock adjustment details"
                :breadcrumbs="[
                    { label: 'Inventory', href: '/app/admin/inventory/stock-adjustment' },
                    { label: 'Stock Adjustment', href: '/app/admin/inventory/stock-adjustment' },
                    { label: adjustment.kode },
                ]"
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" @click="router.visit('/app/admin/inventory/stock-adjustment')">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back
                        </Button>
                        <Button
                            v-if="adjustment.status === 'draft'"
                            variant="outline"
                            @click="router.visit(`/app/admin/inventory/stock-adjustment/${adjustment.id}/edit`)"
                        >
                            Edit
                        </Button>
                        <Button
                            v-if="adjustment.status === 'draft'"
                            class="gap-2"
                            @click="postAdjustment"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            Post
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left: Items -->
                <div class="lg:col-span-2">
                    <Card class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold flex items-center gap-2">
                                <ClipboardList class="h-5 w-5" />
                                Adjustment Items
                            </h3>
                            <Badge :variant="statusVariant(adjustment.status)" class="capitalize">
                                {{ adjustment.status }}
                            </Badge>
                        </div>

                        <div v-if="adjustment.items.length === 0" class="text-center py-10 text-muted-foreground">
                            No items.
                        </div>

                        <div class="rounded-md border overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/50 border-b">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Product</th>
                                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">SKU</th>
                                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Qty</th>
                                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Note</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="item in adjustment.items"
                                        :key="item.id"
                                        class="hover:bg-muted/30 transition-colors"
                                    >
                                        <td class="px-4 py-3 font-medium">{{ item.produk.nama_produk }}</td>
                                        <td class="px-4 py-3 font-mono text-muted-foreground">{{ item.produk.sku }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                :class="[
                                                    'font-semibold',
                                                    item.qty > 0 ? 'text-green-600' : 'text-red-600'
                                                ]"
                                            >
                                                {{ item.qty > 0 ? '+' : '' }}{{ item.qty }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-muted-foreground">{{ item.keterangan || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                <!-- Right: Info -->
                <div class="space-y-6">
                    <Card class="p-6">
                        <h3 class="font-semibold mb-4">Details</h3>

                        <div class="space-y-4 text-sm">
                            <div class="flex items-start gap-3">
                                <Calendar class="h-4 w-4 text-muted-foreground mt-0.5 shrink-0" />
                                <div>
                                    <p class="text-muted-foreground text-xs">Date</p>
                                    <p class="font-medium">{{ formatDate(adjustment.tanggal) }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <MapPin class="h-4 w-4 text-muted-foreground mt-0.5 shrink-0" />
                                <div>
                                    <p class="text-muted-foreground text-xs">Warehouse</p>
                                    <p class="font-medium">{{ adjustment.gudang?.nama_gudang || 'Not specified' }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <User2 class="h-4 w-4 text-muted-foreground mt-0.5 shrink-0" />
                                <div>
                                    <p class="text-muted-foreground text-xs">Created By</p>
                                    <p class="font-medium">{{ adjustment.user?.name || '-' }}</p>
                                </div>
                            </div>

                            <div v-if="adjustment.posted_by" class="flex items-start gap-3">
                                <CheckCircle2 class="h-4 w-4 text-green-600 mt-0.5 shrink-0" />
                                <div>
                                    <p class="text-muted-foreground text-xs">Posted By</p>
                                    <p class="font-medium">{{ adjustment.posted_by.name }}</p>
                                    <p class="text-muted-foreground text-xs">{{ formatDate(adjustment.posted_at) }}</p>
                                </div>
                            </div>

                            <div v-if="adjustment.catatan" class="flex items-start gap-3">
                                <FileText class="h-4 w-4 text-muted-foreground mt-0.5 shrink-0" />
                                <div>
                                    <p class="text-muted-foreground text-xs">Notes</p>
                                    <p>{{ adjustment.catatan }}</p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Card class="p-6">
                        <h3 class="font-semibold mb-3">Summary</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total Items</span>
                                <span class="font-medium">{{ adjustment.items.length }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Net Qty Change</span>
                                <span
                                    :class="[
                                        'font-semibold',
                                        adjustment.items.reduce((s, i) => s + i.qty, 0) >= 0
                                            ? 'text-green-600'
                                            : 'text-red-600'
                                    ]"
                                >
                                    {{ adjustment.items.reduce((s, i) => s + i.qty, 0) >= 0 ? '+' : '' }}
                                    {{ adjustment.items.reduce((s, i) => s + i.qty, 0) }}
                                </span>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
