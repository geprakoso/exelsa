<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import { ArrowLeft, CheckCircle2, PackageSearch, Calendar, MapPin, User2, FileText } from 'lucide-vue-next'

interface Gudang { id: number; nama_gudang: string }
interface UserInfo { id: number; name: string }
interface Produk { id: number; nama_produk: string; sku: string }

interface OpnameItem {
    id: number
    produk: Produk
    stok_sistem: number
    stok_fisik: number
    selisih: number
    catatan: string | null
}

interface Opname {
    id: number
    kode: string
    tanggal: string
    status: 'draft' | 'posted'
    gudang: Gudang | null
    user: UserInfo | null
    posted_by: UserInfo | null
    posted_at: string | null
    catatan: string | null
    items: OpnameItem[]
}

interface PageProps {
    opname: Opname
}

const page = usePage<PageProps>()
const opname = computed(() => page.props.opname)

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'long', year: 'numeric'
    })
}

function postOpname() {
    if (!confirm(`Post stock opname ${opname.value.kode}? This will apply all variances to actual stock. Cannot be undone.`)) return
    router.post(`/app/admin/inventory/stock-opname/${opname.value.id}/post`)
}

function selisihClass(selisih: number): string {
    if (selisih > 0) return 'text-green-600'
    if (selisih < 0) return 'text-red-600'
    return 'text-muted-foreground'
}

const totalSurplus = computed(() => opname.value.items.filter(i => i.selisih > 0).length)
const totalDeficit = computed(() => opname.value.items.filter(i => i.selisih < 0).length)
const totalMatched = computed(() => opname.value.items.filter(i => i.selisih === 0).length)
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                :title="opname.kode"
                description="Stock opname details and variance report"
                :breadcrumbs="[
                    { label: 'Inventory', href: '/app/admin/inventory/stock-opname' },
                    { label: 'Stock Opname', href: '/app/admin/inventory/stock-opname' },
                    { label: opname.kode },
                ]"
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" @click="router.visit('/app/admin/inventory/stock-opname')">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back
                        </Button>
                        <Button
                            v-if="opname.status === 'draft'"
                            variant="outline"
                            @click="router.visit(`/app/admin/inventory/stock-opname/${opname.id}/edit`)"
                        >
                            Edit
                        </Button>
                        <Button
                            v-if="opname.status === 'draft'"
                            class="gap-2"
                            @click="postOpname"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            Post
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <Card class="p-4 text-center">
                    <p class="text-2xl font-bold">{{ opname.items.length }}</p>
                    <p class="text-xs text-muted-foreground mt-1">Total Items</p>
                </Card>
                <Card class="p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ totalSurplus }}</p>
                    <p class="text-xs text-muted-foreground mt-1">Surplus</p>
                </Card>
                <Card class="p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">{{ totalDeficit }}</p>
                    <p class="text-xs text-muted-foreground mt-1">Deficit</p>
                </Card>
                <Card class="p-4 text-center">
                    <p class="text-2xl font-bold text-muted-foreground">{{ totalMatched }}</p>
                    <p class="text-xs text-muted-foreground mt-1">Matched</p>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left: Items Table -->
                <div class="lg:col-span-2">
                    <Card class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold flex items-center gap-2">
                                <PackageSearch class="h-5 w-5" />
                                Count Results
                            </h3>
                            <Badge :variant="opname.status === 'posted' ? 'success' : 'warning'" class="capitalize">
                                {{ opname.status }}
                            </Badge>
                        </div>

                        <div v-if="opname.items.length === 0" class="text-center py-10 text-muted-foreground">
                            No items recorded.
                        </div>

                        <div class="rounded-md border overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/50 border-b">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Product</th>
                                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">System</th>
                                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Physical</th>
                                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Variance</th>
                                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Note</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="item in opname.items"
                                        :key="item.id"
                                        class="hover:bg-muted/30 transition-colors"
                                    >
                                        <td class="px-4 py-3">
                                            <div class="font-medium">{{ item.produk.nama_produk }}</div>
                                            <div class="text-xs text-muted-foreground font-mono">{{ item.produk.sku }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center">{{ item.stok_sistem }}</td>
                                        <td class="px-4 py-3 text-center font-medium">{{ item.stok_fisik }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span :class="['font-semibold', selisihClass(item.selisih)]">
                                                {{ item.selisih > 0 ? '+' : '' }}{{ item.selisih }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-muted-foreground text-xs">{{ item.catatan || '-' }}</td>
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
                                    <p class="font-medium">{{ formatDate(opname.tanggal) }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <MapPin class="h-4 w-4 text-muted-foreground mt-0.5 shrink-0" />
                                <div>
                                    <p class="text-muted-foreground text-xs">Warehouse</p>
                                    <p class="font-medium">{{ opname.gudang?.nama_gudang || 'Not specified' }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <User2 class="h-4 w-4 text-muted-foreground mt-0.5 shrink-0" />
                                <div>
                                    <p class="text-muted-foreground text-xs">Created By</p>
                                    <p class="font-medium">{{ opname.user?.name || '-' }}</p>
                                </div>
                            </div>

                            <div v-if="opname.posted_by" class="flex items-start gap-3">
                                <CheckCircle2 class="h-4 w-4 text-green-600 mt-0.5 shrink-0" />
                                <div>
                                    <p class="text-muted-foreground text-xs">Posted By</p>
                                    <p class="font-medium">{{ opname.posted_by.name }}</p>
                                    <p class="text-muted-foreground text-xs">{{ formatDate(opname.posted_at) }}</p>
                                </div>
                            </div>

                            <div v-if="opname.catatan" class="flex items-start gap-3">
                                <FileText class="h-4 w-4 text-muted-foreground mt-0.5 shrink-0" />
                                <div>
                                    <p class="text-muted-foreground text-xs">Notes</p>
                                    <p>{{ opname.catatan }}</p>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
