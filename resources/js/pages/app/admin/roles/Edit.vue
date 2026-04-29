<script setup lang="ts">
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Card from '@/components/ui/card.vue'
import Input from '@/components/ui/input.vue'
import Button from '@/components/ui/button.vue'
import Checkbox from '@/components/ui/checkbox.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { ArrowLeft, ShieldCheck } from 'lucide-vue-next'

interface PermissionItem {
    id: number
    name: string
    module: string
    action: string
}

interface PermissionGroup {
    module: string
    label: string
    permissions: PermissionItem[]
}

interface RoleDetail {
    id: number
    name: string
    users_count: number
    permissions: string[]
}

const page = usePage()
const role = computed<RoleDetail>(() => page.props.role as RoleDetail)
const permissionGroups = computed<PermissionGroup[]>(() => (page.props.permissionGroups as PermissionGroup[]) || [])

const form = useForm({
    name: role.value.name,
    permissions: [...role.value.permissions],
    is_super_admin: (page.props.isSuperAdmin as boolean) ?? false,
    search: '',
})

const allPermissions = computed(() => permissionGroups.value.flatMap((group) => group.permissions.map((p) => p.name)))

const filteredGroups = computed(() => {
    const query = form.search.toLowerCase().trim()
    if (!query) return permissionGroups.value

    return permissionGroups.value
        .map((group) => ({
            ...group,
            permissions: group.permissions.filter((permission) => {
                const readable = toReadableLabel(permission.name).toLowerCase()
                return permission.name.toLowerCase().includes(query) || readable.includes(query)
            }),
        }))
        .filter((group) => group.permissions.length > 0)
})

const totalSelected = computed(() => form.permissions.length)
const selectedModulesCount = computed(() => {
    return permissionGroups.value.filter((group) => group.permissions.some((permission) => form.permissions.includes(permission.name))).length
})
const selectedReadablePermissions = computed(() => {
    return [...form.permissions].map((permission) => toReadableLabel(permission)).sort()
})

function toReadableLabel(permissionName: string) {
    const [moduleRaw, actionRaw] = permissionName.split('.')
    const module = (moduleRaw || permissionName).replace(/[_-]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
    const action = (actionRaw || 'manage').replace(/[_-]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
    return `${action} ${module}`
}

function isChecked(permissionName: string) {
    return form.permissions.includes(permissionName)
}

function togglePermission(permissionName: string, value: boolean) {
    if (value) {
        if (!form.permissions.includes(permissionName)) {
            form.permissions.push(permissionName)
        }
        return
    }

    form.permissions = form.permissions.filter((permission) => permission !== permissionName)
}

function isModuleFullySelected(group: PermissionGroup) {
    return group.permissions.every((permission) => form.permissions.includes(permission.name))
}

function toggleModule(group: PermissionGroup, value: boolean) {
    const modulePermissions = group.permissions.map((permission) => permission.name)

    if (value) {
        const merged = new Set([...form.permissions, ...modulePermissions])
        form.permissions = Array.from(merged)
        return
    }

    form.permissions = form.permissions.filter((permission) => !modulePermissions.includes(permission))
}

function toggleAll(value: boolean) {
    form.permissions = value ? [...allPermissions.value] : []
}

function submit() {
    form
        .transform((data) => ({
            name: data.name,
            permissions: data.permissions,
            is_super_admin: data.is_super_admin,
        }))
        .put(`/app/admin/roles/${role.value.id}`, {
        preserveScroll: true,
        })
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                :title="`Edit Role: ${role.name}`"
                description="Fine-tune access per module with readable permission controls."
                :breadcrumbs="[
                    { label: 'User Management', href: '/app/admin/users' },
                    { label: 'Roles', href: '/app/admin/roles' },
                    { label: role.name },
                ]"
            >
                <template #actions>
                    <Link href="/app/admin/roles">
                        <Button variant="outline">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back
                        </Button>
                    </Link>
                </template>
            </PageHeader>

            <form class="space-y-6" @submit.prevent="submit">
                <Card class="space-y-5 p-6">
                    <div class="grid gap-4 lg:grid-cols-3">
                        <div class="space-y-2 lg:col-span-2">
                            <label class="text-sm font-medium">Role Name</label>
                            <Input v-model="form.name" :error="!!form.errors.name" placeholder="Role name" />
                        </div>
                        <div class="rounded-lg border bg-muted/30 p-4">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Permission Summary</p>
                            <p class="mt-2 text-2xl font-semibold">{{ totalSelected }}</p>
                            <p class="text-sm text-muted-foreground">selected across {{ selectedModulesCount }} modules</p>
                            <p class="mt-2 text-sm text-muted-foreground">Users with this role: {{ role.users_count }}</p>
                        </div>
                    </div>

                    <div class="grid gap-3 rounded-lg border p-4 md:grid-cols-2">
                        <label class="flex items-center gap-3 rounded-md bg-background p-2">
                            <Checkbox
                                :model-value="form.is_super_admin"
                                @update:model-value="(value) => {
                                    const enabled = !!value
                                    form.is_super_admin = enabled
                                    toggleAll(enabled)
                                }"
                            />
                            <span>
                                <span class="block text-sm font-medium">Super Admin Toggle</span>
                                <span class="block text-xs text-muted-foreground">Grant full access to all permissions.</span>
                            </span>
                        </label>

                        <div class="flex flex-col gap-3 md:items-end">
                            <Input v-model="form.search" placeholder="Search permission" class="w-full md:max-w-sm" />
                            <div class="flex items-center gap-2">
                                <Button type="button" variant="outline" size="sm" @click="toggleAll(true)">Select All</Button>
                                <Button type="button" variant="outline" size="sm" @click="toggleAll(false)">Clear All</Button>
                            </div>
                        </div>
                    </div>
                </Card>

                <div class="grid gap-4 lg:grid-cols-2">
                    <Card
                        v-for="group in filteredGroups"
                        :key="group.module"
                        class="space-y-4 p-5"
                    >
                        <div class="flex items-center justify-between gap-3 border-b pb-3">
                            <div>
                                <p class="text-base font-semibold">{{ group.label }}</p>
                                <p class="text-xs text-muted-foreground">{{ group.permissions.length }} permissions</p>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                :disabled="form.is_super_admin"
                                @click="toggleModule(group, !isModuleFullySelected(group))"
                            >
                                {{ isModuleFullySelected(group) ? 'Unselect all' : 'Select all' }}
                            </Button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[380px] border-collapse text-sm">
                                <thead>
                                    <tr class="border-b text-muted-foreground">
                                        <th class="py-2 text-left font-medium">Action</th>
                                        <th class="py-2 text-left font-medium">Permission</th>
                                        <th class="py-2 text-right font-medium">Enabled</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="permission in group.permissions.sort((a, b) => a.action.localeCompare(b.action))"
                                        :key="permission.id"
                                        class="border-b last:border-b-0"
                                    >
                                        <td class="py-3">
                                            {{ permission.action.replace(/[_-]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }}
                                        </td>
                                        <td class="py-3 text-muted-foreground">
                                            {{ toReadableLabel(permission.name) }}
                                        </td>
                                        <td class="py-3 text-right">
                                            <Checkbox
                                                :model-value="isChecked(permission.name)"
                                                :disabled="form.is_super_admin"
                                                @update:model-value="(value) => togglePermission(permission.name, !!value)"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                <Card class="space-y-4 p-5">
                    <div class="flex items-center justify-between gap-3 border-b pb-3">
                        <p class="text-base font-semibold">Permission Preview</p>
                        <p class="text-xs text-muted-foreground">{{ selectedReadablePermissions.length }} selected</p>
                    </div>
                    <div v-if="selectedReadablePermissions.length" class="grid gap-2 md:grid-cols-2">
                        <div
                            v-for="label in selectedReadablePermissions"
                            :key="label"
                            class="rounded-md border bg-muted/20 px-3 py-2 text-sm"
                        >
                            {{ label }}
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">No permissions selected.</p>
                </Card>

                <div class="sticky bottom-0 z-10 flex items-center justify-end gap-3 rounded-lg border bg-background/95 p-4 backdrop-blur">
                    <Link href="/app/admin/roles">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                    <Button type="submit" :loading="form.processing">
                        <ShieldCheck class="mr-2 h-4 w-4" />
                        Save Role
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
