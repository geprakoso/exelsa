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

interface AkunTransaksi {
    id: number
    kode_akun: string
    nama_akun: string
    nama_bank: string | null
    nama_rekening: string | null
    no_rekening: string | null
    is_active: boolean
    catatan: string | null
}

const akunList = computed<AkunTransaksi[]>(() => page.props.akunTransaksi as AkunTransaksi[] || [])
const isLoading = ref(false)
const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const selectedAkun = ref<AkunTransaksi | null>(null)
const searchQuery = ref('')

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
})

const columns = [
    { key: 'kode_akun', label: 'Code', sortable: true },
    { key: 'nama_akun', label: 'Account Name', sortable: true },
    { key: 'nama_bank', label: 'Bank', sortable: false },
    { key: 'no_rekening', label: 'Account No.', sortable: false },
    { key: 'is_active', label: 'Status', sortable: true },
]

const form = useForm({
    nama_akun: '',
    nama_bank: '',
    nama_rekening: '',
    no_rekening: '',
    is_active: true,
    catatan: '',
})

function openCreateModal() {
    selectedAkun.value = null
    form.reset()
    form.clearErrors()
    showCreateModal.value = true
}

function openEditModal(akun: AkunTransaksi) {
    selectedAkun.value = akun
    form.nama_akun = akun.nama_akun
    form.nama_bank = akun.nama_bank || ''
    form.nama_rekening = akun.nama_rekening || ''
    form.no_rekening = akun.no_rekening || ''
    form.is_active = akun.is_active
    form.catatan = akun.catatan || ''
    showCreateModal.value = true
}

function openDeleteModal(akun: AkunTransaksi) {
    selectedAkun.value = akun
    showDeleteModal.value = true
}

function handleSort(field: string, direction: 'asc' | 'desc') {}
function handlePageChange(page: number) {
    pagination.value.current_page = page
}
function handleRowClick(akun: AkunTransaksi) {
    openEditModal(akun)
}

function submitForm() {
    if (selectedAkun.value) {
        form.put(`/app/admin/master-data/akun-transaksi/${selectedAkun.value.id}`, {
            onSuccess: () => {
                showCreateModal.value = false
                form.reset()
            },
        })
    } else {
        form.post('/app/admin/master-data/akun-transaksi', {
            onSuccess: () => {
                showCreateModal.value = false
                form.reset()
            },
        })
    }
}

function deleteAkun() {
    if (selectedAkun.value) {
        form.delete(`/app/admin/master-data/akun-transaksi/${selectedAkun.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false
                selectedAkun.value = null
            },
        })
    }
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="Transaction Accounts"
                description="Manage your chart of accounts for transactions."
                :breadcrumbs="[
                    { label: 'Master Data', href: '/app/admin/master-data' },
                    { label: 'Transaction Accounts' }
                ]"
            >
                <template #actions>
                    <Button @click="openCreateModal">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Account
                    </Button>
                </template>
            </PageHeader>
            
            <Card class="p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="searchQuery" placeholder="Search accounts..." class="pl-10" />
                    </div>
                </div>
                
                <DataTable
                    :data="akunList"
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
                    {{ selectedAkun ? 'Edit Account' : 'Create Account' }}
                </h2>
                
                <form @submit.prevent="submitForm" class="space-y-4">
                    <FormField label="Account Name" name="nama_akun" required>
                        <Input v-model="form.nama_akun" placeholder="Enter account name" />
                        <p v-if="form.errors.nama_akun" class="text-sm text-red-500">{{ form.errors.nama_akun }}</p>
                    </FormField>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Bank Name" name="nama_bank">
                            <Input v-model="form.nama_bank" placeholder="e.g. BCA, Mandiri" />
                        </FormField>
                        <FormField label="Account Holder" name="nama_rekening">
                            <Input v-model="form.nama_rekening" placeholder="Account holder name" />
                        </FormField>
                    </div>
                    
                    <FormField label="Account Number" name="no_rekening">
                        <Input v-model="form.no_rekening" placeholder="e.g. 1234567890" />
                    </FormField>
                    
                    <FormField label="Notes" name="catatan">
                        <textarea v-model="form.catatan" placeholder="Enter notes" class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" rows="3"></textarea>
                    </FormField>
                    
                    <div class="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
                        <Button type="submit" :loading="form.processing">
                            {{ selectedAkun ? 'Update' : 'Create' }}
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog>
        
        <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event" class="max-w-md">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Delete Account</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete <strong>{{ selectedAkun?.nama_akun }}</strong>?
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="showDeleteModal = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteAkun" :loading="form.processing">Delete</Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
