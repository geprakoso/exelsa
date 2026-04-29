<script setup lang="ts">
import AppLayout from '@/components/layout/AppLayout.vue'
import { ref, h } from 'vue'
import { usePage, useForm, router } from '@inertiajs/vue3'
import { Plus, Search, Pencil, Trash2, MoreHorizontal, User } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Card from '@/components/ui/card.vue'
import Badge from '@/components/ui/badge.vue'
import DataTable from '@/components/tables/DataTable.vue'
import DropdownMenu from '@/components/ui/dropdown-menu/index.vue'
import DropdownMenuContent from '@/components/ui/dropdown-menu/dropdown-menu-content.vue'
import DropdownMenuItem from '@/components/ui/dropdown-menu/dropdown-menu-item.vue'
import DropdownMenuSeparator from '@/components/ui/dropdown-menu/dropdown-menu-separator.vue'
import DropdownMenuTrigger from '@/components/ui/dropdown-menu/dropdown-menu-item.vue'
import Dialog from '@/components/ui/dialog.vue'
import FormField from '@/components/forms/FormField.vue'
import RelationSelect from '@/components/forms/RelationSelect.vue'

const page = usePage()

interface User {
    id: number
    name: string
    email: string
    roles: string[]
    created_at: string
}

const users = ref<User[]>(page.props.users || [])
const isLoading = ref(false)
const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const selectedUser = ref<User | null>(null)

const searchQuery = ref('')
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
})

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'roles', label: 'Roles', sortable: false },
    { key: 'created_at', label: 'Created', sortable: true },
]

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
})

function openCreateModal() {
    form.reset()
    form.clearErrors()
    showCreateModal.value = true
}

function openEditModal(user: User) {
    selectedUser.value = user
    form.name = user.name
    form.email = user.email
    form.roles = user.roles
    showCreateModal.value = true
}

function openDeleteModal(user: User) {
    selectedUser.value = user
    showDeleteModal.value = true
}

function handleSort(field: string, direction: 'asc' | 'desc') {
    // Handle sort
}

function handlePageChange(page: number) {
    pagination.value.current_page = page
    // Handle page change
}

function handleRowClick(user: User) {
    openEditModal(user)
}

function submitForm() {
    if (selectedUser.value) {
        form.put(`/app/admin/users/${selectedUser.value.id}`, {
            onSuccess: () => {
                showCreateModal.value = false
                form.reset()
            },
        })
    } else {
        form.post('/app/admin/users', {
            onSuccess: () => {
                showCreateModal.value = false
                form.reset()
            },
        })
    }
}

function deleteUser() {
    if (selectedUser.value) {
        form.delete(`/app/admin/users/${selectedUser.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false
                selectedUser.value = null
            },
        })
    }
}

const roleOptions = [
    { label: 'Admin', value: 'admin' },
    { label: 'Manager', value: 'manager' },
    { label: 'Kasir', value: 'kasir' },
    { label: 'Gudang', value: 'gudang' },
]
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="Users"
                description="Manage your team members and their permissions."
                :breadcrumbs="[
                    { label: 'Settings', href: '/app/settings' },
                    { label: 'Users' }
                ]"
            >
                <template #actions>
                    <Button @click="openCreateModal">
                        <Plus class="h-4 w-4 mr-2" />
                        Add User
                    </Button>
                </template>
            </PageHeader>
            
            <Card class="p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search users..."
                            class="pl-10"
                        />
                    </div>
                </div>
                
                <DataTable
                    :data="users"
                    :columns="columns"
                    :pagination="pagination"
                    :selectable="true"
                    :loading="isLoading"
                    @sort="handleSort"
                    @page-change="handlePageChange"
                    @row-click="handleRowClick"
                >
                    <template #toolbar>
                        <div class="flex items-center gap-2">
                            <Button variant="outline" size="sm">Export</Button>
                            <Button variant="outline" size="sm" class="text-red-500">Delete Selected</Button>
                        </div>
                    </template>
                    
                    <template #cell:roles="{ row }">
                        <div class="flex gap-1">
                            <Badge v-for="role in row.roles" :key="role" variant="secondary">
                                {{ role }}
                            </Badge>
                        </div>
                    </template>
                    
                    <template #cell:actions="{ row }">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" size="icon">
                                    <MoreHorizontal class="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem @click.stop="openEditModal(row)">
                                    <Pencil class="mr-2 h-4 w-4" />
                                    Edit
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    variant="destructive"
                                    @click.stop="openDeleteModal(row)"
                                >
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>
                </DataTable>
            </Card>
        </div>
        
        <Dialog :open="showCreateModal" @update:open="showCreateModal = $event" class="max-w-md">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">
                    {{ selectedUser ? 'Edit User' : 'Create User' }}
                </h2>
                
                <form @submit.prevent="submitForm" class="space-y-4">
                    <FormField label="Name" name="name" required>
                        <Input v-model="form.name" placeholder="Enter name" :error="!!form.errors.name" />
                        <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                    </FormField>
                    
                    <FormField label="Email" name="email" required>
                        <Input v-model="form.email" type="email" placeholder="Enter email" :error="!!form.errors.email" />
                        <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                    </FormField>
                    
                    <FormField label="Password" :name="selectedUser ? 'password-optional' : 'password'" :required="!selectedUser">
                        <Input
                            v-model="form.password"
                            type="password"
                            :placeholder="selectedUser ? 'Leave blank to keep current' : 'Enter password'"
                            :error="!!form.errors.password"
                        />
                        <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                    </FormField>
                    
                    <FormField label="Confirm Password" name="password_confirmation" :required="!selectedUser || !!form.password">
                        <Input
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Confirm password"
                            :error="!!form.errors.password_confirmation"
                        />
                    </FormField>
                    
                    <FormField label="Roles" name="roles">
                        <RelationSelect
                            v-model="form.roles"
                            :options="roleOptions"
                            placeholder="Select roles"
                            searchable
                        />
                    </FormField>
                    
                    <div class="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" @click="showCreateModal = false">
                            Cancel
                        </Button>
                        <Button type="submit" :loading="form.processing">
                            {{ selectedUser ? 'Update' : 'Create' }}
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog>
        
        <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event" class="max-w-md">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Delete User</h2>
                <p class="text-muted-foreground">
                    Are you sure you want to delete <strong>{{ selectedUser?.name }}</strong>?
                    This action cannot be undone.
                </p>
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="showDeleteModal = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="deleteUser" :loading="form.processing">
                        Delete
                    </Button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
