<script setup lang="ts">
import AppLayout from '@/components/layout/AppLayout.vue'
import { ref } from 'vue'
import { usePage, useForm, router } from '@inertiajs/vue3'
import { Plus, Search, Pencil, Trash2 } from 'lucide-vue-next'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import DataTable from '@/components/tables/DataTable.vue'
import Dialog from '@/components/ui/dialog.vue'
import FormField from '@/components/forms/FormField.vue'
import Badge from '@/components/ui/badge.vue'
import CurrencyInput from '@/components/forms/CurrencyInput.vue'

const page = usePage()

interface Jasa {
    id: number
    slug: string
    nama_jasa: string
    sku: string
    harga: number
    image_url: string | null
    is_active: boolean
    estimasi_waktu_jam: number | null
    deskripsi: string | null
}

const jasaList = ref<Jasa[]>(page.props.jasa || [])
const isLoading = ref(false)
const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const selectedJasa = ref<Jasa | null>(null)
const searchQuery = ref('')

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
})

const columns = [
    { key: 'sku', label: 'SKU', sortable: true },
    { key: 'nama_jasa', label: 'Service Name', sortable: true },
    { key: 'harga_formatted', label: 'Price', sortable: true },
    { key: 'estimasi_waktu_jam', label: 'Est. Time (hrs)', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
]

const form = useForm({
    nama_jasa: '',
    harga: 0,
    estimasi_waktu_jam: null as number | null,
    deskripsi: '',
    image_url: '',
    is_active: true,
})

function openCreateModal() {
    selectedJasa.value = null
    form.reset()
    form.clearErrors()
    showCreateModal.value = true
}

function openEditModal(jasa: Jasa) {
    selectedJasa.value = jasa
    form.nama_jasa = jasa.nama_jasa
    form.harga = jasa.harga
    form.estimasi_waktu_jam = jasa.estimasi_waktu_jam
    form.deskripsi = jasa.deskripsi || ''
    form.image_url = jasa.image_url || ''
    form.is_active = jasa.is_active
    showCreateModal.value = true
}

function openDeleteModal(jasa: Jasa) {
    selectedJasa.value = jasa
    showDeleteModal.value = true
}

function handleSort(field: string, direction: 'asc' | 'desc') {}
function handlePageChange(page: number) {
    pagination.value.current_page = page
}
function handleRowClick(jasa: Jasa) {
    openEditModal(jasa)
}

function submitForm() {
    if (selectedJasa.value) {
        form.put(`/app/admin/master-data/jasa/${selectedJasa.value.id}`, {
            onSuccess: () => {
                showCreateModal.value = false
                form.reset()
                router.reload({ only: ['jasa'] })
            },
        })
    } else {
        form.post('/app/admin/master-data/jasa', {
            onSuccess: () => {
                showCreateModal.value = false
                form.reset()
                router.reload({ only: ['jasa'] })
            },
        })
    }
}

function deleteJasa() {
    if (selectedJasa.value) {
        form.delete(`/app/admin/master-data/jasa/${selectedJasa.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false
                selectedJasa.value = null
                router.reload({ only: ['jasa'] })
            },
        })
    }
}

function formatCurrency(value: number): string {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value)
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="Services"
                description="Manage your services/products."
                :breadcrumbs="[
                    { label: 'Master Data', href: '/app/admin/master-data' },
                    { label: 'Services' }
                ]"
            >
                <template #actions>
                    <Button @click="openCreateModal">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Service
                    </Button>
                </template>
            </PageHeader>
            
            <Card class="p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="searchQuery" placeholder="Search services..." class="pl-10" />
                    </div>
                </div>
                
                <DataTable
                    :data="jasaList"
                    :columns="columns"
                    :pagination="pagination"
                    :loading="isLoading"
                    @sort="handleSort"
                    @page-change="handlePageChange"
                    @row-click="handleRowClick"
                >
                    <template #harga_formatted="{ row }">
                        {{ formatCurrency(row.harga) }}
                    </template>
                    <template #is_active="{ row }">
                        <Badge :variant="row.is_active ? 'default' : 'secondary'">
                            {{ row.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </template>
                    <template #actions="{ row }">
                        <div class="flex items-center gap-2">
                            <Button variant="ghost" size="sm" @click.stop="openEditModal(row)">
                                <Pencil class="h-4 w-4" />
                            </Button>
                            <Button variant="ghost" size="sm" @click.stop="openDeleteModal(row)">
                                <Trash2 class="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                    </template>
                </DataTable>
            </Card>
        </div>
        
        <Dialog :open="showCreateModal" @update:open="showCreateModal = $event" class="max-w-lg">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">
                    {{ selectedJasa ? 'Edit Service' : 'Create Service' }}
                </h2>
                
                <form @submit.prevent="submitForm" class="space-y-4">
                    <FormField label="Service Name" name="nama_jasa" required>
                        <Input v-model="form.nama_jasa" placeholder="Enter service name" />
                        <p v-if="form.errors.nama_jasa" class="text-sm text-red-500">{{ form.errors.nama_jasa }}</p>
                    </FormField>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Price" name="harga" required>
                            <CurrencyInput v-model="form.harga" />
                            <p v-if="form.errors.harga" class="text-sm text-red-500">{{ form.errors.harga }}</p>
                        </FormField>
                        <FormField label="Est. Time (hours)" name="estimasi_waktu_jam">
                            <Input v-model.number="form.estimasi_waktu_jam" type="number" min="0" placeholder="0" />
                        </FormField>
                    </div>
                    
                    <FormField label="Description" name="deskripsi">
                        <textarea v-model="form.deskripsi" placeholder="Enter description" class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" rows="3"></textarea>
                    </FormField>
                    
                    <div class="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
                        <Button type="submit" :loading="form.processing">
                            {{ selectedJasa ? 'Update' : 'Create' }}
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog>
        
        <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event" class="max-w-md">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Delete Service</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete <strong>{{ selectedJasa?.nama_jasa }}</strong>?
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="showDeleteModal = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteJasa" :loading="form.processing">Delete</Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
