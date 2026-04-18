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

const isLoading = ref(false)
const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const selectedKategori = ref<Kategori | null>(null)
const searchQuery = ref('')

const kategoriList = computed(() => {
    const list = (page.props.kategori as Kategori[]) || []
    if (!searchQuery.value) return list
    
    return list.filter(kat => 
        kat.nama_kategori.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        kat.kode.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
})

const columns = [
    { key: 'kode', label: 'Code', sortable: true },
    { key: 'nama_kategori', label: 'Category Name', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
]

const form = useForm({
    nama_kategori: '',
    is_active: true,
})

function openCreateModal() {
    selectedKategori.value = null
    form.reset()
    form.clearErrors()
    showCreateModal.value = true
}

function openEditModal(kategori: Kategori) {
    selectedKategori.value = kategori
    form.nama_kategori = kategori.nama_kategori
    form.is_active = kategori.is_active
    showCreateModal.value = true
}

function openDeleteModal(kategori: Kategori) {
    selectedKategori.value = kategori
    showDeleteModal.value = true
}

function handleSort(field: string, direction: 'asc' | 'desc') {}
function handlePageChange(page: number) {
    pagination.value.current_page = page
}
function handleRowClick(kategori: Kategori) {
    openEditModal(kategori)
}

function submitForm() {
    if (selectedKategori.value) {
        form.put(`/app/admin/master-data/kategori/${selectedKategori.value.id}`, {
            onSuccess: () => {
                showCreateModal.value = false
                form.reset()
            },
        })
    } else {
        form.post('/app/admin/master-data/kategori', {
            onSuccess: () => {
                showCreateModal.value = false
                form.reset()
            },
        })
    }
}

function deleteKategori() {
    if (selectedKategori.value) {
        form.delete(`/app/admin/master-data/kategori/${selectedKategori.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false
                selectedKategori.value = null
            },
        })
    }
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
                    { label: 'Categories' }
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
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="searchQuery" placeholder="Search categories..." class="pl-10" />
                    </div>
                </div>
                
                <DataTable
                    :data="kategoriList"
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
        
        <Dialog :open="showCreateModal" @update:open="showCreateModal = $event" class="max-w-md">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">
                    {{ selectedKategori ? 'Edit Category' : 'Create Category' }}
                </h2>
                
                <form @submit.prevent="submitForm" class="space-y-4">
                    <FormField label="Category Name" name="nama_kategori" required>
                        <Input v-model="form.nama_kategori" placeholder="Enter category name" />
                        <p v-if="form.errors.nama_kategori" class="text-sm text-red-500">{{ form.errors.nama_kategori }}</p>
                    </FormField>
                    
                    <div class="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
                        <Button type="submit" :loading="form.processing">
                            {{ selectedKategori ? 'Update' : 'Create' }}
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog>
        
        <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event" class="max-w-md">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Delete Category</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete <strong>{{ selectedKategori?.nama_kategori }}</strong>?
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="showDeleteModal = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteKategori" :loading="form.processing">Delete</Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
