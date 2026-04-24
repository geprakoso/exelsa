<script setup lang="ts" generic="T extends Record<string, any>">
import { ref, computed, onMounted, watch, h, useSlots, renderSlot, shallowRef } from 'vue'
import {
    useVueTable,
    createColumnHelper,
    FlexRender,
    getCoreRowModel,
    getSortedRowModel,
    getPaginationRowModel,
    type SortingState,
    type PaginationState,
    type ColumnDef,
} from '@tanstack/vue-table'
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    MoreHorizontal,
    Check,
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import Table from '@/components/ui/table.vue'
import TableHeader from '@/components/ui/table-header.vue'
import TableBody from '@/components/ui/table-body.vue'
import TableRow from '@/components/ui/table-row.vue'
import TableHead from '@/components/ui/table-head.vue'
import TableCell from '@/components/ui/table-cell.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Checkbox from '@/components/ui/checkbox.vue'
import DropdownMenu from '@/components/ui/dropdown-menu/index.vue'
import DropdownMenuContent from '@/components/ui/dropdown-menu/dropdown-menu-content.vue'
import DropdownMenuItem from '@/components/ui/dropdown-menu/dropdown-menu-item.vue'
import DropdownMenuSeparator from '@/components/ui/dropdown-menu/dropdown-menu-separator.vue'
import DropdownMenuTrigger from '@/components/ui/dropdown-menu/dropdown-menu-item.vue'

interface TableColumn<T> {
    key: string
    label: string
    sortable?: boolean
    visible?: boolean
    width?: string
    align?: 'left' | 'center' | 'right'
}

const props = defineProps<{
    data: T[]
    columns: TableColumn<T>[]
    pagination?: {
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    selectable?: boolean
    loading?: boolean
    class?: string
}>()

const emit = defineEmits<{
    'sort': [field: string, direction: 'asc' | 'desc']
    'page-change': [page: number]
    'row-click': [row: T]
    'bulk-action': [ids: string[]]
}>()

const sorting = ref<SortingState>([])
const pagination = ref<PaginationState>({
    pageIndex: (props.pagination?.current_page || 1) - 1,
    pageSize: props.pagination?.per_page || 15,
})
const rowSelection = ref<Record<string, boolean>>({})

const slots = useSlots()
const columnHelper = createColumnHelper<T>()

// Use shallowRef to prevent re-renders on deep changes
const tableColumns = shallowRef<ColumnDef<T, any>[]>([])

// Function to build columns - only called when dependencies change
function buildColumns() {
    const cols: ColumnDef<T, any>[] = []

    if (props.selectable) {
        cols.push({
            id: 'select',
            header: ({ table }) => {
                const allSelected = table.getIsAllRowsSelected()
                const someSelected = table.getIsSomeRowsSelected()
                return h(Checkbox, {
                    checked: allSelected || someSelected ? someSelected && !allSelected ? 'indeterminate' : true : false,
                    'onUpdate:checked': (val: boolean | 'indeterminate') => table.toggleAllRowsSelected(!!val),
                    ariaLabel: 'Select all',
                })
            },
            cell: ({ row }) => h(Checkbox, {
                checked: row.getIsSelected(),
                'onUpdate:checked': (val: boolean) => row.toggleSelected(!!val),
                ariaLabel: 'Select row',
            }),
            enableSorting: false,
        })
    }

    for (const col of props.columns) {
        if (col.visible === false) continue

        cols.push({
            accessorKey: col.key,
            header: ({ column }) => {
                if (!col.sortable) return col.label
                return h('button', {
                    class: 'flex items-center gap-1 hover:text-foreground',
                    onClick: () => {
                        const isAsc = column.getIsSorted() === 'asc'
                        column.toggleSorting(isAsc)
                        emit('sort', col.key, isAsc ? 'desc' : 'asc')
                    },
                }, [
                    col.label,
                    column.getIsSorted() === 'asc' ? h(ArrowUp, { class: 'h-4 w-4' }) :
                    column.getIsSorted() === 'desc' ? h(ArrowDown, { class: 'h-4 w-4' }) :
                    h(ArrowUpDown, { class: 'h-4 w-4 opacity-50' }),
                ])
            },
            cell: ({ row }) => {
                const value = row.getValue(col.key)
                const slotName = `cell:${col.key}`
                // Check if parent provided a custom cell slot
                if (slots[slotName]) {
                    // Pass row data and value to slot - use row.original for raw data access
                    const rowData = row.original || row
                    const slotProps = { row: rowData, value, original: row.original }
                    const slotContent = slots[slotName](slotProps)
                    // Return a wrapper div with the slot content
                    return h('div', {}, slotContent)
                }
                // Handle object values - extract common name properties
                if (value && typeof value === 'object') {
                    return value.nama_brand || value.nama_kategori || value.name || JSON.stringify(value)
                }
                return value
            },
            enableSorting: col.sortable,
        })
    }

    tableColumns.value = cols
}

// Watch only the specific props that should trigger column rebuild
watch(() => [props.columns, props.selectable], buildColumns, { immediate: true, deep: true })

const table = useVueTable({
    get data() { return props.data },
    get columns() { return tableColumns.value },
    get rowSelection() { return rowSelection.value },
    onRowSelectionChange: (updater) => {
        rowSelection.value = typeof updater === 'function' ? updater(rowSelection.value) : updater
    },
    state: {
        get sorting() { return sorting.value },
        get pagination() { return pagination.value },
    },
    onSortingChange: (updater) => {
        sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater
    },
    onPaginationChange: (updater) => {
        pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
})

const selectedIds = computed(() => {
    return Object.keys(rowSelection.value).filter(id => rowSelection.value[id])
})

function goToPage(page: number) {
    pagination.value.pageIndex = page - 1
    emit('page-change', page)
}
</script>

<template>
    <div :class="cn('space-y-4', props.class)">
        <div v-if="$slots.toolbar" class="flex items-center justify-between">
            <slot name="toolbar" />
        </div>
        
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead v-for="header in headerGroup.headers" :key="header.id">
                            <FlexRender
                                v-if="!header.isPlaceholder"
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="loading">
                        <TableRow v-for="i in 5" :key="i">
                            <TableCell v-for="col in (props.selectable ? props.columns.length + 1 : props.columns.length)" :key="col">
                                <div class="h-4 w-full animate-pulse rounded bg-muted" />
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else-if="table.getRowModel().rows?.length">
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            :data-state="row.getIsSelected() ? 'selected' : undefined"
                            class="cursor-pointer"
                            @click="emit('row-click', row.original)"
                        >
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow>
                            <TableCell :colspan="props.columns.length + (props.selectable ? 1 : 0)" class="h-24 text-center">
                                No results.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>
        
        <div v-if="pagination" class="flex items-center justify-between">
            <div class="text-sm text-muted-foreground">
                Showing {{ pagination.pageIndex * pagination.pageSize + 1 }} to
                {{ Math.min((pagination.pageIndex + 1) * pagination.pageSize, props.pagination?.total || 0) }}
                of {{ props.pagination?.total || 0 }} entries
                <span v-if="selectedIds.length > 0">
                    ({{ selectedIds.length }} selected)
                </span>
            </div>
            
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!table.getCanPreviousPage()"
                    @click="table.previousPage()"
                >
                    <ChevronLeft class="h-4 w-4" />
                </Button>
                <div class="flex items-center gap-1">
                    <Button
                        v-for="page in (props.pagination?.last_page || 1)"
                        :key="page"
                        :variant="pagination.pageIndex + 1 === page ? 'default' : 'outline'"
                        size="sm"
                        class="w-10"
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </Button>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!table.getCanNextPage()"
                    @click="table.nextPage()"
                >
                    <ChevronRight class="h-4 w-4" />
                </Button>
            </div>
        </div>
        
        <slot name="bulk-actions" :selected="selectedIds" />
    </div>
</template>
