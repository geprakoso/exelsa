<script setup lang="ts">
import AppLayout from '@/components/layout/AppLayout.vue'
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { Plus, Search, Pencil, Trash2, KeyRound } from 'lucide-vue-next'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import DataTable from '@/components/tables/DataTable.vue'
import Dialog from '@/components/ui/dialog.vue'
import FormField from '@/components/forms/FormField.vue'
import Badge from '@/components/ui/badge.vue'

interface PermissionItem {
    id: number
    name: string
    roles_count: number
}

const page = usePage()
const permissions = computed<PermissionItem[]>(() => (page.props.permissions as PermissionItem[]) || [])
const searchQuery = ref('')
const showFormModal = ref(false)
const showDeleteModal = ref(false)
const selectedPermission = ref<PermissionItem | null>(null)
const isDeleting = ref(false)

const form = useForm({
    name: '',
})

const columns = [
    { key: 'name', label: 'Permission', sortable: true },
    { key: 'roles_count', label: 'Roles', sortable: true },
]

const filteredPermissions = computed(() => {
    const query = searchQuery.value.toLowerCase().trim()
    if (!query) return permissions.value
    return permissions.value.filter((permission) => permission.name.toLowerCase().includes(query))
})

function openCreateModal() {
    selectedPermission.value = null
    form.reset()
    form.clearErrors()
    showFormModal.value = true
}

function openEditModal(permission: PermissionItem) {
    selectedPermission.value = permission
    form.name = permission.name
    form.clearErrors()
    showFormModal.value = true
}

function openDeleteModal(permission: PermissionItem) {
    selectedPermission.value = permission
    showDeleteModal.value = true
}

function closeFormModal() {
    showFormModal.value = false
    selectedPermission.value = null
    form.reset()
    form.clearErrors()
}

function closeDeleteModal() {
    showDeleteModal.value = false
    selectedPermission.value = null
}

function submitForm() {
    if (selectedPermission.value) {
        form.put(`/app/admin/permissions/${selectedPermission.value.id}`, {
            onSuccess: closeFormModal,
        })
        return
    }

    form.post('/app/admin/permissions', {
        onSuccess: closeFormModal,
    })
}

function deletePermission() {
    if (!selectedPermission.value) return
    isDeleting.value = true
    form.delete(`/app/admin/permissions/${selectedPermission.value.id}`, {
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
                title="Permissions"
                description="Manage granular access capabilities across roles."
                :breadcrumbs="[
                    { label: 'User Management', href: '/app/admin/users' },
                    { label: 'Permissions' },
                ]"
            >
                <template #actions>
                    <Button @click="openCreateModal">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Permission
                    </Button>
                </template>
            </PageHeader>

            <Card class="p-6">
                <div class="mb-6 flex items-center gap-4">
                    <div class="relative max-w-sm flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="searchQuery" placeholder="Search permissions..." class="pl-10" />
                    </div>
                </div>

                <DataTable :data="filteredPermissions" :columns="columns">
                    <template #cell:name="{ row }">
                        <div class="flex items-center gap-2">
                            <KeyRound class="h-4 w-4 text-muted-foreground" />
                            <span>{{ row.name }}</span>
                        </div>
                    </template>

                    <template #cell:roles_count="{ row }">
                        <Badge variant="secondary">
                            {{ row.roles_count }}
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

        <Dialog :open="showFormModal" @update:open="showFormModal = $event" class="max-w-md">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">{{ selectedPermission ? 'Edit Permission' : 'Create Permission' }}</h2>

                <form class="space-y-4" @submit.prevent="submitForm">
                    <FormField label="Name" name="name" :error="form.errors.name" required>
                        <Input v-model="form.name" placeholder="Enter permission name" :error="!!form.errors.name" />
                    </FormField>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" @click="closeFormModal">Cancel</Button>
                        <Button type="submit" :loading="form.processing">{{ selectedPermission ? 'Update' : 'Create' }}</Button>
                    </div>
                </form>
            </div>
        </Dialog>

        <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event" class="max-w-md">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Delete Permission</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete <strong>{{ selectedPermission?.name }}</strong>?
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="closeDeleteModal">Cancel</Button>
                    <Button variant="destructive" :loading="isDeleting" @click="deletePermission">Delete</Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
