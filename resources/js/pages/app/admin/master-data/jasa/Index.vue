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

// Data dari props - client side untuk data kecil
const jasaList = computed<Jasa[]>(() => (page.props.jasa as Jasa[]) || [])

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

// Filtered jasa - client side
const filteredJasa = computed(() => {
    const query = searchQuery.value.toLowerCase().trim()
    if (!query) return jasaList.value

    return jasaList.value.filter(
        (j) =>
            j.nama_jasa.toLowerCase().includes(query) ||
            j.sku.toLowerCase().includes(query)
    )
})

// Loading states
const isLoading = computed(() => isSearching.value)
const isDeleting = ref(false)

// Modal states
const showFormModal = ref(false)
const showDeleteModal = ref(false)
const selectedJasa = ref<Jasa | null>(null)

const form = useForm({
    nama_jasa: '',
    harga: 0,
    estimasi_waktu_jam: null as number | null,
    deskripsi: '',
    image_url: '',
    is_active: true,
})

const columns = [
    { key: 'sku', label: 'SKU', sortable: true },
    { key: 'nama_jasa', label: 'Service Name', sortable: true },
    { key: 'harga', label: 'Price', sortable: true },
    { key: 'estimasi_waktu_jam', label: 'Est. Time (hrs)', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
]

function openCreateModal() {
    selectedJasa.value = null
    form.reset()
    form.clearErrors()
    showFormModal.value = true
}

function openEditModal(jasa: Jasa) {
    selectedJasa.value = jasa
    form.nama_jasa = jasa.nama_jasa
    form.harga = jasa.harga
    form.estimasi_waktu_jam = jasa.estimasi_waktu_jam
    form.deskripsi = jasa.deskripsi || ''
    form.image_url = jasa.image_url || ''
    form.is_active = jasa.is_active
    showFormModal.value = true
}

function openDeleteModal(jasa: Jasa) {
    selectedJasa.value = jasa
    showDeleteModal.value = true
}

function closeFormModal() {
    showFormModal.value = false
    selectedJasa.value = null
    form.reset()
    form.clearErrors()
}

function closeDeleteModal() {
    showDeleteModal.value = false
    selectedJasa.value = null
}

function handleSort(field: string, direction: 'asc' | 'desc') {
    console.log('Sort:', field, direction)
}

function handlePageChange(page: number) {
    console.log('Page:', page)
}

function handleRowClick(jasa: Jasa) {
    openEditModal(jasa)
}

function submitForm() {
    if (selectedJasa.value) {
        form.put(`/app/admin/master-data/jasa/${selectedJasa.value.id}`, {
            onSuccess: () => {
                closeFormModal()
            },
        })
    } else {
        form.post('/app/admin/master-data/jasa', {
            onSuccess: () => {
                closeFormModal()
            },
        })
    }
}

function deleteJasa() {
    if (!selectedJasa.value) return

    isDeleting.value = true
    form.delete(`/app/admin/master-data/jasa/${selectedJasa.value.id}`, {
        onSuccess: () => {
            closeDeleteModal()
            isDeleting.value = false
        },
        onError: () => {
            isDeleting.value = false
        },
    })
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
                    { label: 'Services' },
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
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            :model-value="searchQuery"
                            @update:model-value="handleSearchInput"
                            placeholder="Search services..."
                            class="pl-10"
                        />
                    </div>
                    <div v-if="isSearching" class="text-sm text-muted-foreground">
                        Searching...
                    </div>
                </div>

                <DataTable
                    :data="filteredJasa"
                    :columns="columns"
                    :loading="isLoading"
                    @sort="handleSort"
                    @page-change="handlePageChange"
                    @row-click="handleRowClick"
                >
                    <template #harga="{ row }">
                        {{ formatCurrency(row.harga) }}
                    </template>
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
                    v-if="filteredJasa.length === 0 && !isLoading"
                    class="text-center py-8 text-muted-foreground"
                >
                    No services found
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
                    {{ selectedJasa ? 'Edit Service' : 'Create Service' }}
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <FormField
                        label="Service Name"
                        name="nama_jasa"
                        :error="form.errors.nama_jasa"
                        required
                    >
                        <Input
                            v-model="form.nama_jasa"
                            placeholder="Enter service name"
                        />
                    </FormField>

                    <div class="grid grid-cols-2 gap-4">
                        <FormField
                            label="Price"
                            name="harga"
                            :error="form.errors.harga"
                            required
                        >
                            <CurrencyInput v-model="form.harga" />
                        </FormField>
                        <FormField
                            label="Est. Time (hours)"
                            name="estimasi_waktu_jam"
                            :error="form.errors.estimasi_waktu_jam"
                        >
                            <Input
                                v-model.number="form.estimasi_waktu_jam"
                                type="number"
                                min="0"
                                placeholder="0"
                            />
                        </FormField>
                    </div>

                    <FormField
                        label="Description"
                        name="deskripsi"
                        :error="form.errors.deskripsi"
                    >
                        <textarea
                            v-model="form.deskripsi"
                            placeholder="Enter description"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            rows="3"
                        ></textarea>
                    </FormField>

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
                            {{ selectedJasa ? 'Update' : 'Create' }}
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
                <h2 class="text-lg font-semibold">Delete Service</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete
                    <strong>{{ selectedJasa?.nama_jasa }}</strong>?
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="closeDeleteModal">
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="deleteJasa"
                        :loading="isDeleting"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
