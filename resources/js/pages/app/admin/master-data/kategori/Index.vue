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

interface Kategori {
    id: number
    slug: string
    nama_kategori: string
    kode: string
    is_active: boolean
}

// Data dari props - client side untuk data kecil
const kategoriList = computed<Kategori[]>(
    () => (page.props.kategori as Kategori[]) || []
)

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

// Filtered categories - client side
const filteredKategori = computed(() => {
    const query = searchQuery.value.toLowerCase().trim()
    if (!query) return kategoriList.value

    return kategoriList.value.filter(
        (kat) =>
            kat.nama_kategori.toLowerCase().includes(query) ||
            kat.kode.toLowerCase().includes(query)
    )
})

// Loading states
const isLoading = computed(() => isSearching.value)
const isDeleting = ref(false)

// Modal states
const showFormModal = ref(false)
const showDeleteModal = ref(false)
const selectedKategori = ref<Kategori | null>(null)

const form = useForm({
    nama_kategori: '',
    is_active: true,
})

const columns = [
    { key: 'kode', label: 'Code', sortable: true },
    { key: 'nama_kategori', label: 'Category Name', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
]

function openCreateModal() {
    selectedKategori.value = null
    form.reset()
    form.clearErrors()
    showFormModal.value = true
}

function openEditModal(kategori: Kategori) {
    selectedKategori.value = kategori
    form.nama_kategori = kategori.nama_kategori
    form.is_active = kategori.is_active
    showFormModal.value = true
}

function openDeleteModal(kategori: Kategori) {
    selectedKategori.value = kategori
    showDeleteModal.value = true
}

function closeFormModal() {
    showFormModal.value = false
    selectedKategori.value = null
    form.reset()
    form.clearErrors()
}

function closeDeleteModal() {
    showDeleteModal.value = false
    selectedKategori.value = null
}

function handleSort(field: string, direction: 'asc' | 'desc') {
    console.log('Sort:', field, direction)
}

function handlePageChange(page: number) {
    console.log('Page:', page)
}

function handleRowClick(kategori: Kategori) {
    openEditModal(kategori)
}

function submitForm() {
    if (selectedKategori.value) {
        form.put(`/app/admin/master-data/kategori/${selectedKategori.value.id}`, {
            onSuccess: () => {
                closeFormModal()
            },
        })
    } else {
        form.post('/app/admin/master-data/kategori', {
            onSuccess: () => {
                closeFormModal()
            },
        })
    }
}

function deleteKategori() {
    if (!selectedKategori.value) return

    isDeleting.value = true
    form.delete(`/app/admin/master-data/kategori/${selectedKategori.value.id}`, {
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
                title="Categories"
                description="Manage your product categories."
                :breadcrumbs="[
                    { label: 'Master Data', href: '/app/admin/master-data' },
                    { label: 'Categories' },
                ]"
            >
                <template #actions>
                    <Button @click="openCreateModal">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Category
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
                            placeholder="Search categories..."
                            class="pl-10"
                        />
                    </div>
                    <div v-if="isSearching" class="text-sm text-muted-foreground">
                        Searching...
                    </div>
                </div>

                <DataTable
                    :data="filteredKategori"
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
                    v-if="filteredKategori.length === 0 && !isLoading"
                    class="text-center py-8 text-muted-foreground"
                >
                    No categories found
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
                    {{ selectedKategori ? 'Edit Category' : 'Create Category' }}
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <FormField
                        label="Category Name"
                        name="nama_kategori"
                        :error="form.errors.nama_kategori"
                        required
                    >
                        <Input
                            v-model="form.nama_kategori"
                            :error="!!form.errors.nama_kategori"
                            placeholder="Enter category name"
                        />
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
                            {{ selectedKategori ? 'Update' : 'Create' }}
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
                <h2 class="text-lg font-semibold">Delete Category</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete
                    <strong>{{ selectedKategori?.nama_kategori }}</strong>?
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="closeDeleteModal">
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="deleteKategori"
                        :loading="isDeleting"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
