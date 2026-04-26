<script setup lang="ts">
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import { ArrowLeft, Printer, Pencil, Trash2, User, Package } from 'lucide-vue-next'

const page = usePage()

const penjualan = computed(() => page.props.penjualan)

function formatCurrency(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    })
}

function printInvoice() {
    window.print()
}

function deletePenjualan() {
    if (confirm('Are you sure you want to delete this transaction?')) {
        router.delete(`/app/admin/transactions/penjualan/${penjualan.value.id_penjualan}`)
    }
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                :title="penjualan?.no_nota || 'Transaction Details'"
                :description="penjualan ? formatDate(penjualan.tanggal_penjualan) : ''"
                :breadcrumbs="[
                    { label: 'Transactions', href: '/app/admin/transactions/penjualan' },
                    { label: penjualan?.no_nota || 'Details' }
                ]"
            >
                <template #actions>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="printInvoice">
                            <Printer class="h-4 w-4 mr-2" />
                            Print
                        </Button>
                        <Link :href="`/app/admin/transactions/penjualan/${penjualan?.id_penjualan}/edit`">
                            <Button>
                                <Pencil class="h-4 w-4 mr-2" />
                                Edit
                            </Button>
                        </Link>
                        <Button variant="destructive" @click="deletePenjualan">
                            <Trash2 class="h-4 w-4 mr-2" />
                            Delete
                        </Button>
                    </div>
                </template>
            </PageHeader>
            
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left Column: Main Info + Items -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Items Table -->
                    <Card class="p-6">
                        <h3 class="font-semibold mb-4 flex items-center gap-2">
                            <Package class="h-5 w-5" />
                            Items
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-muted/50 border-b">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-sm font-medium text-muted-foreground">Product</th>
                                        <th class="px-3 py-2 text-right text-sm font-medium text-muted-foreground">Qty</th>
                                        <th class="px-3 py-2 text-right text-sm font-medium text-muted-foreground">Price</th>
                                        <th class="px-3 py-2 text-right text-sm font-medium text-muted-foreground">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="item in penjualan?.items" :key="item.id_penjualan_item">
                                        <td class="px-3 py-3">
                                            <p class="font-medium">{{ item.produk?.nama_produk || 'Unknown' }}</p>
                                            <p class="text-xs text-muted-foreground">{{ item.produk?.sku || '-' }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-right">{{ item.qty }}</td>
                                        <td class="px-3 py-3 text-right">{{ formatCurrency(item.harga_jual) }}</td>
                                        <td class="px-3 py-3 text-right font-medium">
                                            {{ formatCurrency(item.qty * item.harga_jual) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!penjualan?.items?.length">
                                        <td colspan="4" class="px-3 py-4 text-center text-muted-foreground">
                                            No items
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                    
                    <!-- Notes -->
                    <Card v-if="penjualan?.catatan" class="p-6">
                        <h3 class="font-semibold mb-2">Notes</h3>
                        <p class="text-muted-foreground">{{ penjualan.catatan }}</p>
                    </Card>
                </div>
                
                <!-- Right Column: Summary + Customer -->
                <div class="space-y-6">
                    <!-- Payment Status -->
                    <Card class="p-6">
                        <h3 class="font-semibold mb-4">Payment Status</h3>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-muted-foreground">Status</span>
                            <Badge
                                :variant="penjualan?.status_pembayaran === 'lunas' ? 'success' : 'destructive'"
                                class="text-sm"
                            >
                                {{ penjualan?.status_pembayaran === 'lunas' ? 'LUNAS' : 'BELUM LUNAS' }}
                            </Badge>
                        </div>
                        
                        <div class="space-y-3 pt-4 border-t">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Subtotal</span>
                                <span>{{ formatCurrency(penjualan?.total || 0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Discount</span>
                                <span class="text-red-600">-{{ formatCurrency(penjualan?.diskon_total || 0) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold pt-2 border-t">
                                <span>Grand Total</span>
                                <span>{{ formatCurrency(penjualan?.grand_total || 0) }}</span>
                            </div>
                        </div>
                    </Card>
                    
                    <!-- Customer Info -->
                    <Card class="p-6">
                        <h3 class="font-semibold mb-4 flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Customer
                        </h3>
                        <div v-if="penjualan?.member" class="space-y-2">
                            <p class="font-medium">{{ penjualan.member.nama_member }}</p>
                            <p class="text-sm text-muted-foreground">{{ penjualan.member.kode_member }}</p>
                        </div>
                        <p v-else class="text-muted-foreground">Guest / No customer</p>
                    </Card>
                    
                    <!-- Cashier Info -->
                    <Card v-if="penjualan?.karyawan" class="p-6">
                        <h3 class="font-semibold mb-4">Cashier</h3>
                        <p>{{ penjualan.karyawan.nama_karyawan }}</p>
                    </Card>
                    
                    <!-- Payments -->
                    <Card v-if="penjualan?.pembayaran?.length" class="p-6">
                        <h3 class="font-semibold mb-4">Payments</h3>
                        <div class="space-y-3">
                            <div
                                v-for="payment in penjualan.pembayaran"
                                :key="payment.id"
                                class="flex justify-between"
                            >
                                <span class="text-muted-foreground">{{ payment.metode_bayar }}</span>
                                <span class="font-medium">{{ formatCurrency(payment.jumlah) }}</span>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    button, a, nav, header { display: none !important; }
}
</style>
