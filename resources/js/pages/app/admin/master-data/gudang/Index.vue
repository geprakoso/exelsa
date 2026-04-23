<script setup lang="ts">
import AppLayout from '@/components/layout/AppLayout.vue'
import { ref, computed } from 'vue'
import { usePage, useForm } from '@inertiajs/vue3'
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

// Data dari props - client side untuk data kecil
const gudangList = computed<Gudang[]>(() => (page.props.gudang as Gudang[]) || [])

// Search dengan debounce
const searchQuery = ref('')
const isSearching = ref(false)
let searchTimeout: ReturnType<typeof setTimeout> | null = null

function handleSearchInput(value: string) {
    searchQuery.value = value
    isSearching.value = true

    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        isSearching.value = false
    }, 300)
}

// Filtered gudang - client side
const filteredGudang = computed(() => {
    const query = searchQuery.value.toLowerCase().trim()
    if (!query) return gudangList.value

    return gudangList.value.filter(
        (g) =>
            g.nama_gudang.toLowerCase().includes(query) ||
            (g.lokasi_gudang?.toLowerCase().includes(query) ?? false) ||
            (g.kota?.toLowerCase().includes(query) ?? false)
    )
})

// Loading states
const isLoading = computed(() => isSearching.value)
const isDeleting = ref(false)

// Modal states
const showFormModal = ref(false)
const showDeleteModal = ref(false)
const selectedGudang = ref<Gudang | null>(null)

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

const columns = [
    { key: 'nama_gudang', label: 'Warehouse Name', sortable: true },
    { key: 'lokasi_gudang', label: 'Location', sortable: false },
    { key: 'kota', label: 'City', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
]

function openCreateModal() {
    selectedGudang.value = null
    form.reset()
    form.clearErrors()
    showFormModal.value = true
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
    showFormModal.value = true
}

function openDeleteModal(gudang: Gudang) {
    selectedGudang.value = gudang
    showDeleteModal.value = true
}

function closeFormModal() {
    showFormModal.value = false
    selectedGudang.value = null
    form.reset()
    form.clearErrors()
}

function closeDeleteModal() {
    showDeleteModal.value = false
    selectedGudang.value = null
}

function handleSort(field: string, direction: 'asc' | 'desc') {
    console.log('Sort:', field, direction)
}

function handlePageChange(page: number) {
    console.log('Page:', page)
}

function handleRowClick(gudang: Gudang) {
    openEditModal(gudang)
}

function submitForm() {
    if (selectedGudang.value) {
        form.put(`/app/admin/master-data/gudang/${selectedGudang.value.id}`, {
            onSuccess: () => {
                closeFormModal()
            },
        })
    } else {
        form.post('/app/admin/master-data/gudang', {
            onSuccess: () => {
                closeFormModal()
            },
        })
    }
}

function deleteGudang() {
    if (!selectedGudang.value) return

    isDeleting.value = true
    form.delete(`/app/admin/master-data/gudang/${selectedGudang.value.id}`, {
        onSuccess: () => {
            closeDeleteModal()
            isDeleting.value = false
        },
        onError: () => {
            isDeleting.value = false
        },
    })
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
                    { label: 'Warehouses' },
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
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            :model-value="searchQuery"
                            @update:model-value="handleSearchInput"
                            placeholder="Search warehouses..."
                            class="pl-10"
                        />
                    </div>
                    <div v-if="isSearching" class="text-sm text-muted-foreground">
                        Searching...
                    </div>
                </div>

                <DataTable
                    :data="filteredGudang"
                    :columns="columns"
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
                            <Button
                                variant="ghost"
                                size="sm"
                                @click.stop="openEditModal(row)"
                            >
                                <Pencil class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click.stop="openDeleteModal(row)"
                            >
                                <Trash2 class="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                    </template>
                </DataTable>

                <div
                    v-if="filteredGudang.length === 0 && !isLoading"
                    class="text-center py-8 text-muted-foreground"
                >
                    No warehouses found
                </div>
            </Card>
        </div>

        <!-- Create/Edit Modal -->
        <Dialog
            :open="showFormModal"
            @update:open="showFormModal = $event"
            class="max-w-lg"
        >
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">
                    {{ selectedGudang ? 'Edit Warehouse' : 'Create Warehouse' }}
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <FormField
                        label="Warehouse Name"
                        name="nama_gudang"
                        :error="form.errors.nama_gudang"
                        required
                    >
                        <Input
                            v-model="form.nama_gudang"
                            placeholder="Enter warehouse name"
                        />
                    </FormField>

                    <FormField
                        label="Location Address"
                        name="lokasi_gudang"
                        :error="form.errors.lokasi_gudang"
                    >
                        <Input
                            v-model="form.lokasi_gudang"
                            placeholder="Enter location address"
                        />
                    </FormField>

                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Province" name="provinsi" :error="form.errors.provinsi">
                            <Input v-model="form.provinsi" placeholder="Provinsi" />
                        </FormField>
                        <FormField label="City" name="kota" :error="form.errors.kota">
                            <Input v-model="form.kota" placeholder="Kota" />
                        </FormField>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="District" name="kecamatan" :error="form.errors.kecamatan">
                            <Input v-model="form.kecamatan" placeholder="Kecamatan" />
                        </FormField>
                        <FormField
                            label="Sub-district"
                            name="kelurahan"
                            :error="form.errors.kelurahan"
                        >
                            <Input v-model="form.kelurahan" placeholder="Kelurahan" />
                        </FormField>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <FormField label="Latitude" name="latitude" :error="form.errors.latitude">
                            <Input
                                v-model.number="form.latitude"
                                type="number"
                                step="any"
                                placeholder="-6.2"
                            />
                        </FormField>
                        <FormField label="Longitude" name="longitude" :error="form.errors.longitude">
                            <Input
                                v-model.number="form.longitude"
                                type="number"
                                step="any"
                                placeholder="106.8"
                            />
                        </FormField>
                        <FormField label="Radius (km)" name="radius_km" :error="form.errors.radius_km">
                            <Input
                                v-model.number="form.radius_km"
                                type="number"
                                step="0.1"
                                min="0"
                                placeholder="1"
                            />
                        </FormField>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300"
                        />
                        <label for="is_active" class="text-sm">Active</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" @click="closeFormModal">
                            Cancel
                        </Button>
                        <Button type="submit" :loading="form.processing">
                            {{ selectedGudang ? 'Update' : 'Create' }}
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog>

        <!-- Delete Modal -->
        <Dialog
            :open="showDeleteModal"
            @update:open="showDeleteModal = $event"
            class="max-w-md"
        >
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Delete Warehouse</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete
                    <strong>{{ selectedGudang?.nama_gudang }}</strong>?
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="closeDeleteModal">
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="deleteGudang"
                        :loading="isDeleting"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
