<script setup lang="ts">
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import { ArrowLeft, Printer, Pencil, Trash2, Package, Truck } from 'lucide-vue-next'

const page = usePage()

const pembelian = computed(() => page.props.pembelian)

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

function deletePembelian() {
    if (confirm('Are you sure you want to delete this purchase?')) {
        router.delete(`/app/admin/transactions/pembelian/${pembelian.value.id_pembelian}`)
    }
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                :title="pembelian?.no_po || 'Purchase Details'"
                :description="pembelian ? formatDate(pembelian.tanggal) : ''"
                :breadcrumbs="[
                    { label: 'Transactions', href: '/app/admin/transactions' },
                    { label: 'Pembelian', href: '/app/admin/transactions/pembelian' },
                    { label: pembelian?.no_po || 'Details' }
                ]"
            >
                <template #actions>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="printInvoice">
                            <Printer class="h-4 w-4 mr-2" />
                            Print
                        </Button>
                        <Link :href="`/app/admin/transactions/pembelian/${pembelian?.id_pembelian}/edit`">
                            <Button>
                                <Pencil class="h-4 w-4 mr-2" />
                                Edit
                            </Button>
                        </Link>
                        <Button variant="destructive" @click="deletePembelian">
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
                                        <th class="px-3 py-2 text-center text-sm font-medium text-muted-foreground">Qty</th>
                                        <th class="px-3 py-2 text-center text-sm font-medium text-muted-foreground">Masuk</th>
                                        <th class="px-3 py-2 text-center text-sm font-medium text-muted-foreground">Sisa</th>
                                        <th class="px-3 py-2 text-right text-sm font-medium text-muted-foreground">Price</th>
                                        <th class="px-3 py-2 text-right text-sm font-medium text-muted-foreground">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="item in pembelian?.items" :key="item.id_pembelian_item">
                                        <td class="px-3 py-3">
                                            <p class="font-medium">{{ item.produk?.nama_produk || 'Unknown' }}</p>
                                            <p class="text-xs text-muted-foreground">{{ item.produk?.sku || '-' }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-center">{{ item.qty }}</td>
                                        <td class="px-3 py-3 text-center">{{ item.qty_masuk || 0 }}</td>
                                        <td class="px-3 py-3 text-center">{{ item.qty_sisa || item.qty }}</td>
                                        <td class="px-3 py-3 text-right">{{ formatCurrency(item.harga_jual) }}</td>
                                        <td class="px-3 py-3 text-right font-medium">
                                            {{ formatCurrency((item.qty || 0) * (item.harga_jual || 0)) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!pembelian?.items?.length">
                                        <td colspan="6" class="px-3 py-4 text-center text-muted-foreground">
                                            No items
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                    
                    <!-- Notes -->
                    <Card v-if="pembelian?.catatan" class="p-6">
                        <h3 class="font-semibold mb-2">Notes</h3>
                        <p class="text-muted-foreground">{{ pembelian.catatan }}</p>
                    </Card>
                </div>
                
                <!-- Right Column: Summary + Supplier -->
                <div class="space-y-6">
                    <!-- Summary -->
                    <Card class="p-6">
                        <h3 class="font-semibold mb-4">Summary</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Payment Type</span>
                                <Badge
                                    :variant="pembelian?.jenis_pembayaran === 'cash' ? 'success' : 'secondary'"
                                >
                                    {{ pembelian?.jenis_pembayaran === 'cash' ? 'Cash' : 'Kredit' }}
                                </Badge>
                            </div>
                            
                            <div v-if="pembelian?.tgl_tempo" class="flex justify-between">
                                <span class="text-muted-foreground">Due Date</span>
                                <span>{{ formatDate(pembelian.tgl_tempo) }}</span>
                            </div>
                            
                            <div class="flex justify-between text-lg font-bold pt-3 border-t">
                                <span>Total</span>
                                <span>{{ formatCurrency(pembelian?.harga_jual || 0) }}</span>
                            </div>
                        </div>
                    </Card>
                    
                    <!-- Supplier Info -->
                    <Card class="p-6">
                        <h3 class="font-semibold mb-4 flex items-center gap-2">
                            <Truck class="h-5 w-5" />
                            Supplier
                        </h3>
                        <div v-if="pembelian?.supplier" class="space-y-2">
                            <p class="font-medium">{{ pembelian.supplier.nama_supplier }}</p>
                        </div>
                        <p v-else class="text-muted-foreground">No supplier</p>
                        
                        <div v-if="pembelian?.nota_supplier" class="mt-4 pt-4 border-t">
                            <p class="text-sm text-muted-foreground">Nota Supplier</p>
                            <p class="font-medium">{{ pembelian.nota_supplier }}</p>
                        </div>
                    </Card>
                    
                    <!-- Staff Info -->
                    <Card v-if="pembelian?.karyawan" class="p-6">
                        <h3 class="font-semibold mb-4">Staff</h3>
                        <p>{{ pembelian.karyawan.nama_karyawan }}</p>
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
