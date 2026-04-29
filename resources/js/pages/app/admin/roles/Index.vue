<script setup lang="ts">
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Card from '@/components/ui/card.vue'
import Input from '@/components/ui/input.vue'
import Button from '@/components/ui/button.vue'
import Badge from '@/components/ui/badge.vue'
import DataTable from '@/components/tables/DataTable.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Search, ShieldCheck, Pencil, Trash2, Plus } from 'lucide-vue-next'

interface RoleItem {
    id: number
    name: string
    permissions_count: number
    users_count: number
}

const page = usePage()
const roles = computed<RoleItem[]>(() => (page.props.roles as RoleItem[]) || [])
const searchQuery = ref('')
const form = useForm({ name: '' })

const columns = [
    { key: 'name', label: 'Role Name', sortable: true },
    { key: 'permissions_count', label: 'Permissions', sortable: true },
    { key: 'users_count', label: 'Users', sortable: true },
    { key: 'actions', label: 'Actions', sortable: false },
]

const filteredRoles = computed(() => {
    const q = searchQuery.value.toLowerCase().trim()
    if (!q) return roles.value
    return roles.value.filter((role) => role.name.toLowerCase().includes(q))
})

function createRole() {
    form.post('/app/admin/roles', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}

function deleteRole(roleId: number) {
    if (!window.confirm('Delete this role?')) return
    form.delete(`/app/admin/roles/${roleId}`, { preserveScroll: true })
}

function openRole(role: RoleItem) {
    window.location.href = `/app/admin/roles/${role.id}/edit`
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="Roles"
                description="Manage role access with clear ownership and permission coverage."
                :breadcrumbs="[
                    { label: 'User Management', href: '/app/admin/users' },
                    { label: 'Roles' },
                ]"
            />

            <Card class="space-y-5 p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="relative w-full max-w-md">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="searchQuery" placeholder="Search roles" class="pl-10" />
                    </div>

                    <form class="flex w-full flex-col gap-3 md:w-auto md:flex-row" @submit.prevent="createRole">
                        <Input v-model="form.name" placeholder="New role name" class="md:w-64" :error="!!form.errors.name" />
                        <Button type="submit" :loading="form.processing">
                            <Plus class="mr-2 h-4 w-4" />
                            Add Role
                        </Button>
                    </form>
                </div>

                <DataTable :data="filteredRoles" :columns="columns" @row-click="openRole">
                    <template #cell:name="{ row }">
                        <div class="flex items-center gap-2 font-medium">
                            <ShieldCheck class="h-4 w-4 text-muted-foreground" />
                            <span>{{ row.name }}</span>
                        </div>
                    </template>

                    <template #cell:permissions_count="{ row }">
                        <Badge variant="secondary">{{ row.permissions_count }}</Badge>
                    </template>

                    <template #cell:users_count="{ row }">
                        <Badge variant="secondary">{{ row.users_count }}</Badge>
                    </template>

                    <template #actions="{ row }">
                        <div class="flex items-center gap-2">
                            <Link :href="`/app/admin/roles/${row.id}/edit`">
                                <Button variant="ghost" size="sm">
                                    <Pencil class="h-4 w-4" />
                                </Button>
                            </Link>
                            <Button variant="ghost" size="sm" @click.stop="deleteRole(row.id)">
                                <Trash2 class="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                    </template>
                </DataTable>
            </Card>
        </div>
    </AppLayout>
</template>
