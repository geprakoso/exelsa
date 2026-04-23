<script setup lang="ts">
import AppLayout from '@/components/layout/AppLayout.vue'
import { computed, ref, watch } from 'vue'
import { usePage, useForm, router } from '@inertiajs/vue3'
import { Plus, Search, Pencil, Trash2 } from 'lucide-vue-next'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import DataTable from '@/components/tables/DataTable.vue'
import Dialog from '@/components/ui/dialog.vue'
import FormField from '@/components/forms/FormField.vue'

const page = usePage()

interface Supplier {
    id: number
    nama_supplier: string
    email: string | null
    no_hp: string
    alamat: string | null
    provinsi: string | null
    kota: string | null
    kecamatan: string | null
}

interface PaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

interface PageProps {
    suppliers: {
        data: Supplier[]
    } & PaginationMeta
    filters: {
        search?: string
    }
}

const typedPage = computed(() => page.props as unknown as PageProps)

// Data dari props
const suppliers = computed(() => typedPage.value.suppliers.data)
const paginationMeta = computed(() => ({
    current_page: typedPage.value.suppliers.current_page,
    last_page: typedPage.value.suppliers.last_page,
    per_page: typedPage.value.suppliers.per_page,
    total: typedPage.value.suppliers.total,
}))

// Search dengan debounce
const searchQuery = ref(typedPage.value.filters.search || '')
let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch(searchQuery, (newValue) => {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(
            '/app/admin/master-data/supplier',
            { search: newValue || undefined },
            { preserveState: true, replace: true }
        )
    }, 300)
})

// Loading states
const isLoading = ref(false)
const isDeleting = ref(false)

// Modal states
const showFormModal = ref(false)
const showDeleteModal = ref(false)
const selectedSupplier = ref<Supplier | null>(null)

const form = useForm({
    nama_supplier: '',
    email: '',
    no_hp: '',
    alamat: '',
    provinsi: '',
    kota: '',
    kecamatan: '',
})

const columns = [
    { key: 'nama_supplier', label: 'Supplier', sortable: true },
    { key: 'no_hp', label: 'Phone', sortable: true },
    { key: 'email', label: 'Email', sortable: false },
    { key: 'kota', label: 'City', sortable: true },
]

function openCreateModal() {
    selectedSupplier.value = null
    form.reset()
    form.clearErrors()
    showFormModal.value = true
}

function openEditModal(supplier: Supplier) {
    selectedSupplier.value = supplier
    form.nama_supplier = supplier.nama_supplier
    form.email = supplier.email || ''
    form.no_hp = supplier.no_hp
    form.alamat = supplier.alamat || ''
    form.provinsi = supplier.provinsi || ''
    form.kota = supplier.kota || ''
    form.kecamatan = supplier.kecamatan || ''
    showFormModal.value = true
}

function openDeleteModal(supplier: Supplier) {
    selectedSupplier.value = supplier
    showDeleteModal.value = true
}

function closeFormModal() {
    showFormModal.value = false
    selectedSupplier.value = null
    form.reset()
    form.clearErrors()
}

function closeDeleteModal() {
    showDeleteModal.value = false
    selectedSupplier.value = null
}

function handleSort(field: string, direction: 'asc' | 'desc') {
    console.log('Sort:', field, direction)
}

function handlePageChange(pageNum: number) {
    isLoading.value = true
    router.get(
        '/app/admin/master-data/supplier',
        {
            page: pageNum,
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            onFinish: () => {
                isLoading.value = false
            },
        }
    )
}

function handleRowClick(supplier: Supplier) {
    openEditModal(supplier)
}

function submitForm() {
    if (selectedSupplier.value) {
        form.put(`/app/admin/master-data/supplier/${selectedSupplier.value.id}`, {
            onSuccess: () => {
                closeFormModal()
            },
        })
    } else {
        form.post('/app/admin/master-data/supplier', {
            onSuccess: () => {
                closeFormModal()
            },
        })
    }
}

function deleteSupplier() {
    if (!selectedSupplier.value) return

    isDeleting.value = true
    form.delete(`/app/admin/master-data/supplier/${selectedSupplier.value.id}`, {
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
                title="Suppliers"
                description="Manage your product suppliers."
                :breadcrumbs="[
                    { label: 'Master Data', href: '/app/admin/master-data' },
                    { label: 'Suppliers' },
                ]"
            >
                <template #actions>
                    <Button @click="openCreateModal">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Supplier
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
                            v-model="searchQuery"
                            placeholder="Search suppliers..."
                            class="pl-10"
                        />
                    </div>
                </div>

                <DataTable
                    :data="suppliers"
                    :columns="columns"
                    :pagination="paginationMeta"
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
                    {{ selectedSupplier ? 'Edit Supplier' : 'Create Supplier' }}
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <FormField
                        label="Nama Supplier"
                        name="nama_supplier"
                        :error="form.errors.nama_supplier"
                        required
                    >
                        <Input
                            v-model="form.nama_supplier"
                            placeholder="Enter supplier name"
                        />
                    </FormField>

                    <FormField label="No. HP" name="no_hp" :error="form.errors.no_hp" required>
                        <Input v-model="form.no_hp" placeholder="08xxxxxxxxxx" />
                    </FormField>

                    <FormField label="Email" name="email" :error="form.errors.email">
                        <Input
                            v-model="form.email"
                            type="email"
                            placeholder="email@supplier.com"
                        />
                    </FormField>

                    <FormField label="Alamat" name="alamat" :error="form.errors.alamat">
                        <textarea
                            v-model="form.alamat"
                            placeholder="Enter address"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            rows="3"
                        ></textarea>
                    </FormField>

                    <div class="grid grid-cols-3 gap-4">
                        <FormField
                            label="Provinsi"
                            name="provinsi"
                            :error="form.errors.provinsi"
                        >
                            <Input v-model="form.provinsi" placeholder="Provinsi" />
                        </FormField>
                        <FormField label="Kota" name="kota" :error="form.errors.kota">
                            <Input v-model="form.kota" placeholder="Kota" />
                        </FormField>
                        <FormField
                            label="Kecamatan"
                            name="kecamatan"
                            :error="form.errors.kecamatan"
                        >
                            <Input v-model="form.kecamatan" placeholder="Kecamatan" />
                        </FormField>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" @click="closeFormModal">
                            Cancel
                        </Button>
                        <Button type="submit" :loading="form.processing">
                            {{ selectedSupplier ? 'Update' : 'Create' }}
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
                <h2 class="text-lg font-semibold">Delete Supplier</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete
                    <strong>{{ selectedSupplier?.nama_supplier }}</strong>?
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="closeDeleteModal">
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="deleteSupplier"
                        :loading="isDeleting"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
