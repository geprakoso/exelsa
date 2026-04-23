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

const page = usePage()

interface Brand {
    id: number
    nama_brand: string
}

// Data dari props - client side untuk data kecil
const brands = computed<Brand[]>(() => (page.props.brands as Brand[]) || [])

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

// Filtered brands - client side
const filteredBrands = computed(() => {
    const query = searchQuery.value.toLowerCase().trim()
    if (!query) return brands.value

    return brands.value.filter((brand) =>
        brand.nama_brand.toLowerCase().includes(query)
    )
})

// Loading states
const isLoading = computed(() => isSearching.value)
const isDeleting = ref(false)

// Modal states
const showFormModal = ref(false)
const showDeleteModal = ref(false)
const selectedBrand = ref<Brand | null>(null)

const form = useForm({
    nama_brand: '',
})

const columns = [{ key: 'nama_brand', label: 'Name', sortable: true }]

function openCreateModal() {
    selectedBrand.value = null
    form.reset()
    form.clearErrors()
    showFormModal.value = true
}

function openEditModal(brand: Brand) {
    selectedBrand.value = brand
    form.nama_brand = brand.nama_brand
    showFormModal.value = true
}

function openDeleteModal(brand: Brand) {
    selectedBrand.value = brand
    showDeleteModal.value = true
}

function closeFormModal() {
    showFormModal.value = false
    selectedBrand.value = null
    form.reset()
    form.clearErrors()
}

function closeDeleteModal() {
    showDeleteModal.value = false
    selectedBrand.value = null
}

function handleSort(field: string, direction: 'asc' | 'desc') {
    // Client-side sort bisa ditambahkan di sini
    console.log('Sort:', field, direction)
}

function handlePageChange(page: number) {
    // Client-side pagination - tidak perlu hit server
    console.log('Page:', page)
}

function handleRowClick(brand: Brand) {
    openEditModal(brand)
}

function submitForm() {
    if (selectedBrand.value) {
        form.put(`/app/admin/master-data/brand/${selectedBrand.value.id}`, {
            onSuccess: () => {
                closeFormModal()
            },
        })
    } else {
        form.post('/app/admin/master-data/brand', {
            onSuccess: () => {
                closeFormModal()
            },
        })
    }
}

function deleteBrand() {
    if (!selectedBrand.value) return

    isDeleting.value = true
    form.delete(`/app/admin/master-data/brand/${selectedBrand.value.id}`, {
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
                title="Brands"
                description="Manage your product brands."
                :breadcrumbs="[
                    { label: 'Master Data', href: '/app/admin/master-data' },
                    { label: 'Brands' },
                ]"
            >
                <template #actions>
                    <Button @click="openCreateModal">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Brand
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
                            placeholder="Search brands..."
                            class="pl-10"
                        />
                    </div>
                    <div v-if="isSearching" class="text-sm text-muted-foreground">
                        Searching...
                    </div>
                </div>

                <DataTable
                    :data="filteredBrands"
                    :columns="columns"
                    :loading="isLoading"
                    @sort="handleSort"
                    @page-change="handlePageChange"
                    @row-click="handleRowClick"
                >
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
                    v-if="filteredBrands.length === 0 && !isLoading"
                    class="text-center py-8 text-muted-foreground"
                >
                    No brands found
                </div>
            </Card>
        </div>

        <!-- Create/Edit Modal -->
        <Dialog
            :open="showFormModal"
            @update:open="showFormModal = $event"
            class="max-w-md"
        >
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">
                    {{ selectedBrand ? 'Edit Brand' : 'Create Brand' }}
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <FormField
                        label="Name"
                        name="nama_brand"
                        :error="form.errors.nama_brand"
                        required
                    >
                        <Input
                            v-model="form.nama_brand"
                            :error="!!form.errors.nama_brand"
                            placeholder="Enter brand name"
                        />
                    </FormField>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" @click="closeFormModal">
                            Cancel
                        </Button>
                        <Button type="submit" :loading="form.processing">
                            {{ selectedBrand ? 'Update' : 'Create' }}
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
                <h2 class="text-lg font-semibold">Delete Brand</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete
                    <strong>{{ selectedBrand?.nama_brand }}</strong>?
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="closeDeleteModal">
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="deleteBrand"
                        :loading="isDeleting"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
