<script setup lang="ts">
import AppLayout from '@/components/layout/AppLayout.vue'
import { ref, computed } from 'vue'
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

const page = usePage()

interface Gudang {
    id: number
    nama_gudang: string
    lokasi_gudang: string | null
    provinsi: string | null
    kota: string | null
    kecamatan: string | null
    kelurahan: string | null
    latitude: number | null
    longitude: number | null
    radius_km: number | null
    is_active: boolean
}

const gudangList = computed<Gudang[]>(() => page.props.gudang as Gudang[] || [])
const isLoading = ref(false)
const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const selectedGudang = ref<Gudang | null>(null)
const searchQuery = ref('')

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
})

const columns = [
    { key: 'nama_gudang', label: 'Warehouse Name', sortable: true },
    { key: 'lokasi_gudang', label: 'Location', sortable: false },
    { key: 'kota', label: 'City', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
]

const form = useForm({
    nama_gudang: '',
    lokasi_gudang: '',
    provinsi: '',
    kota: '',
    kecamatan: '',
    kelurahan: '',
    latitude: null as number | null,
    longitude: null as number | null,
    radius_km: null as number | null,
    is_active: true,
})

function openCreateModal() {
    selectedGudang.value = null
    form.reset()
    form.clearErrors()
    showCreateModal.value = true
}

function openEditModal(gudang: Gudang) {
    selectedGudang.value = gudang
    form.nama_gudang = gudang.nama_gudang
    form.lokasi_gudang = gudang.lokasi_gudang || ''
    form.provinsi = gudang.provinsi || ''
    form.kota = gudang.kota || ''
    form.kecamatan = gudang.kecamatan || ''
    form.kelurahan = gudang.kelurahan || ''
    form.latitude = gudang.latitude
    form.longitude = gudang.longitude
    form.radius_km = gudang.radius_km
    form.is_active = gudang.is_active
    showCreateModal.value = true
}

function openDeleteModal(gudang: Gudang) {
    selectedGudang.value = gudang
    showDeleteModal.value = true
}

function handleSort(field: string, direction: 'asc' | 'desc') {}
function handlePageChange(page: number) {
    pagination.value.current_page = page
}
function handleRowClick(gudang: Gudang) {
    openEditModal(gudang)
}

function submitForm() {
    if (selectedGudang.value) {
        form.put(`/app/admin/master-data/gudang/${selectedGudang.value.id}`, {
            onSuccess: () => {
                showCreateModal.value = false
                form.reset()
            },
        })
    } else {
        form.post('/app/admin/master-data/gudang', {
            onSuccess: () => {
                showCreateModal.value = false
                form.reset()
            },
        })
    }
}

function deleteGudang() {
    if (selectedGudang.value) {
        form.delete(`/app/admin/master-data/gudang/${selectedGudang.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false
                selectedGudang.value = null
            },
        })
    }
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="Warehouses"
                description="Manage your warehouses/storage locations."
                :breadcrumbs="[
                    { label: 'Master Data', href: '/app/admin/master-data' },
                    { label: 'Warehouses' }
                ]"
            >
                <template #actions>
                    <Button @click="openCreateModal">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Warehouse
                    </Button>
                </template>
            </PageHeader>
            
            <Card class="p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="searchQuery" placeholder="Search warehouses..." class="pl-10" />
                    </div>
                </div>
                
                <DataTable
                    :data="gudangList"
                    :columns="columns"
                    :pagination="pagination"
                    :loading="isLoading"
                    @sort="handleSort"
                    @page-change="handlePageChange"
                    @row-click="handleRowClick"
                >
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
                    {{ selectedGudang ? 'Edit Warehouse' : 'Create Warehouse' }}
                </h2>
                
                <form @submit.prevent="submitForm" class="space-y-4">
                    <FormField label="Warehouse Name" name="nama_gudang" required>
                        <Input v-model="form.nama_gudang" placeholder="Enter warehouse name" />
                        <p v-if="form.errors.nama_gudang" class="text-sm text-red-500">{{ form.errors.nama_gudang }}</p>
                    </FormField>
                    
                    <FormField label="Location Address" name="lokasi_gudang">
                        <Input v-model="form.lokasi_gudang" placeholder="Enter location address" />
                    </FormField>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Province" name="provinsi">
                            <Input v-model="form.provinsi" placeholder="Provinsi" />
                        </FormField>
                        <FormField label="City" name="kota">
                            <Input v-model="form.kota" placeholder="Kota" />
                        </FormField>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="District" name="kecamatan">
                            <Input v-model="form.kecamatan" placeholder="Kecamatan" />
                        </FormField>
                        <FormField label="Sub-district" name="kelurahan">
                            <Input v-model="form.kelurahan" placeholder="Kelurahan" />
                        </FormField>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <FormField label="Latitude" name="latitude">
                            <Input v-model.number="form.latitude" type="number" step="any" placeholder="-6.2" />
                        </FormField>
                        <FormField label="Longitude" name="longitude">
                            <Input v-model.number="form.longitude" type="number" step="any" placeholder="106.8" />
                        </FormField>
                        <FormField label="Radius (km)" name="radius_km">
                            <Input v-model.number="form.radius_km" type="number" step="0.1" min="0" placeholder="1" />
                        </FormField>
                    </div>
                    
                    <div class="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
                        <Button type="submit" :loading="form.processing">
                            {{ selectedGudang ? 'Update' : 'Create' }}
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog>
        
        <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event" class="max-w-md">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Delete Warehouse</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete <strong>{{ selectedGudang?.nama_gudang }}</strong>?
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="showDeleteModal = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteGudang" :loading="form.processing">Delete</Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
