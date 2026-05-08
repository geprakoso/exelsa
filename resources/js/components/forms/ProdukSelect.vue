<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { onClickOutside } from '@vueuse/core'
import {
    Check,
    ChevronsUpDown,
    Search,
    X,
    Loader2,
    Package,
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import axios from 'axios'

export interface ProdukOption {
    id: number
    nama_produk: string
    sku: string
    brand: { id: number; nama_brand: string } | null
    kategori: { id: number; nama_kategori: string } | null
    image_url: string | null
    stok_on_hand?: number
}

interface Props {
    modelValue?: number | null
    placeholder?: string
    searchPlaceholder?: string
    emptyMessage?: string
    disabled?: boolean
    class?: string
    endpoint?: string
    inStockOnly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Search product...',
    searchPlaceholder: 'Search by name or SKU...',
    emptyMessage: 'No products found.',
    endpoint: '/app/admin/master-data/produk/search',
})

const emit = defineEmits<{
    'update:modelValue': [value: number | null]
    'select': [produk: ProdukOption]
}>()

const api = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
})

if (typeof document !== 'undefined') {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (token) {
        api.defaults.headers.common['X-CSRF-TOKEN'] = token
    }
}

const isOpen = ref(false)
const searchQuery = ref('')
const options = ref<ProdukOption[]>([])
const loading = ref(false)
const triggerRef = ref<HTMLButtonElement | null>(null)
const dropdownRef = ref<HTMLDivElement | null>(null)
const searchInputRef = ref<HTMLInputElement | null>(null)
const activeIndex = ref(-1)
const ignoreNextClick = ref(false)

const dropdownPosition = ref({
    top: 0,
    left: 0,
    width: 0,
})

const selectedOption = computed(() => {
    if (!props.modelValue) return null
    return options.value.find((opt) => opt.id === props.modelValue) ?? null
})

const initialLoading = ref(false)

function getDetailUrl(id: number): string {
    const base = props.endpoint.replace(/\/search$/, '')
    return `${base}/${id}`
}

async function loadSelectedProduct() {
    if (!props.modelValue) return
    if (selectedOption.value) return
    initialLoading.value = true
    try {
        const { data } = await api.get(getDetailUrl(props.modelValue))
        if (data && data.id === props.modelValue) {
            const existing = options.value.find((opt) => opt.id === props.modelValue)
            if (!existing) {
                options.value = [data, ...options.value]
            }
        }
    } catch (e) {
        console.error('Failed to load selected product:', e)
    } finally {
        initialLoading.value = false
    }
}

onMounted(() => {
    loadSelectedProduct()
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

function fetchOptions(query: string) {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(async () => {
        loading.value = true
        try {
            const { data } = await api.get(props.endpoint, {
                params: { q: query, limit: 50, in_stock: props.inStockOnly ? 1 : undefined },
            })
            options.value = data
        } catch (e) {
            console.error('Failed to fetch products:', e)
        } finally {
            loading.value = false
        }
    }, 250)
}

function updatePosition() {
    if (!triggerRef.value) return
    const rect = triggerRef.value.getBoundingClientRect()
    const viewportHeight = window.innerHeight
    const spaceBelow = viewportHeight - rect.bottom
    const dropdownHeight = Math.min(320, options.value.length * 44 + 120)
    const openAbove = spaceBelow < dropdownHeight && rect.top > dropdownHeight

    dropdownPosition.value = {
        top: openAbove ? rect.top - dropdownHeight : rect.bottom + 4,
        left: rect.left,
        width: rect.width,
    }
}

function openDropdown() {
    if (props.disabled) return
    isOpen.value = true
    activeIndex.value = -1
    nextTick(() => {
        updatePosition()
        searchInputRef.value?.focus()
    })
    window.addEventListener('scroll', updatePosition, true)
    window.addEventListener('resize', updatePosition)

    if (selectedOption.value) {
        searchQuery.value = ''
    } else {
        fetchOptions('')
    }
}

function handleFocus() {
    if (!isOpen.value && !props.disabled) {
        ignoreNextClick.value = true
        openDropdown()
    }
}

function closeDropdown() {
    isOpen.value = false
    searchQuery.value = ''
    activeIndex.value = -1
    if (searchTimeout) clearTimeout(searchTimeout)
    window.removeEventListener('scroll', updatePosition, true)
    window.removeEventListener('resize', updatePosition)
}

function toggleDropdown() {
    if (ignoreNextClick.value) {
        ignoreNextClick.value = false
        return
    }
    if (isOpen.value) {
        closeDropdown()
    } else {
        openDropdown()
    }
}

function selectOption(option: ProdukOption) {
    emit('update:modelValue', option.id)
    emit('select', option)
    closeDropdown()
}

function clearSelection(e: MouseEvent) {
    e.stopPropagation()
    emit('update:modelValue', null)
    searchQuery.value = ''
    options.value = []
}

function handleKeydown(e: KeyboardEvent) {
    if (!isOpen.value) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault()
            openDropdown()
        }
        return
    }

    switch (e.key) {
        case 'Escape':
            e.preventDefault()
            closeDropdown()
            break
        case 'ArrowDown':
            e.preventDefault()
            activeIndex.value = Math.min(activeIndex.value + 1, options.value.length - 1)
            scrollToActive()
            break
        case 'ArrowUp':
            e.preventDefault()
            activeIndex.value = Math.max(activeIndex.value - 1, 0)
            scrollToActive()
            break
        case 'Enter':
            e.preventDefault()
            if (activeIndex.value >= 0 && options.value[activeIndex.value]) {
                selectOption(options.value[activeIndex.value])
            }
            break
        case 'Tab':
            e.preventDefault()
            if (activeIndex.value >= 0 && options.value[activeIndex.value]) {
                selectOption(options.value[activeIndex.value])
            } else {
                closeDropdown()
            }
            nextTick(() => {
                triggerRef.value?.blur()
            })
            break
    }
}

function scrollToActive() {
    nextTick(() => {
        const el = dropdownRef.value?.querySelector(`[data-index="${activeIndex.value}"]`)
        el?.scrollIntoView({ block: 'nearest' })
    })
}

watch(searchQuery, (val) => {
    fetchOptions(val)
})

watch(
    () => props.modelValue,
    () => {
        searchQuery.value = ''
        loadSelectedProduct()
    }
)

onClickOutside(dropdownRef, () => {
    if (isOpen.value) closeDropdown()
})

defineExpose({
    focus: () => triggerRef.value?.focus(),
    open: openDropdown,
    close: closeDropdown,
})
</script>

<template>
    <div :class="cn('relative w-full', props.class)">
        <button
            ref="triggerRef"
            type="button"
            role="combobox"
            :aria-expanded="isOpen"
            :aria-controls="isOpen ? 'produk-select-dropdown' : undefined"
            :disabled="disabled"
            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-colors hover:bg-accent/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            :class="{ 'border-ring ring-2 ring-ring ring-offset-2': isOpen }"
            @click="toggleDropdown"
            @keydown="handleKeydown"
            @focus="handleFocus"
        >
            <span class="flex items-center gap-2 min-w-0 flex-1">
                <Package class="h-4 w-4 shrink-0 text-muted-foreground" />
                <template v-if="initialLoading">
                    <Loader2 class="h-4 w-4 animate-spin text-muted-foreground" />
                    <span class="text-muted-foreground truncate">Loading...</span>
                </template>
                <span v-else-if="selectedOption" class="truncate font-medium">
                    {{ selectedOption.nama_produk }}
                    <span class="text-muted-foreground font-normal text-xs ml-1">
                        {{ selectedOption.sku }}
                    </span>
                </span>
                <span v-else class="text-muted-foreground truncate">
                    {{ placeholder }}
                </span>
            </span>
            <span class="flex items-center gap-1 shrink-0 ml-2">
                <button
                    v-if="modelValue && !disabled"
                    type="button"
                    class="inline-flex h-5 w-5 items-center justify-center rounded-md hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                    @click="clearSelection"
                    tabindex="-1"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
                <span v-if="modelValue && !disabled" class="h-4 w-[1px] bg-border mx-1" />
                <Loader2 v-if="(loading && isOpen) || initialLoading" class="h-4 w-4 animate-spin text-muted-foreground" />
                <ChevronsUpDown
                    v-else
                    class="h-4 w-4 text-muted-foreground transition-transform duration-200"
                    :class="{ 'rotate-180': isOpen }"
                />
            </span>
        </button>

        <Teleport to="body">
            <div
                v-if="isOpen"
                id="produk-select-dropdown"
                ref="dropdownRef"
                class="fixed z-50 rounded-md border bg-popover text-popover-foreground shadow-md outline-none animate-in fade-in-0 zoom-in-95"
                :style="{
                    top: `${dropdownPosition.top}px`,
                    left: `${dropdownPosition.left}px`,
                    width: `${dropdownPosition.width}px`,
                }"
            >
                <div class="flex items-center border-b border-gray-100 px-3 py-2 gap-2">
                    <Search class="h-4 w-4 shrink-0 text-muted-foreground" />
                    <input
                        ref="searchInputRef"
                        v-model="searchQuery"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="search-input-no-border flex h-8 w-full bg-transparent text-sm border-0 outline-none ring-0 focus:ring-0 focus:outline-none focus:border-0 placeholder:text-muted-foreground"
                        @keydown="handleKeydown"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        class="inline-flex h-5 w-5 items-center justify-center rounded-md hover:bg-muted text-muted-foreground"
                        @click="searchQuery = ''"
                    >
                        <X class="h-3 w-3" />
                    </button>
                </div>

                <div class="max-h-[300px] overflow-auto p-1" role="listbox">
                    <div
                        v-if="loading"
                        class="flex items-center justify-center py-8 text-muted-foreground"
                    >
                        <Loader2 class="h-5 w-5 animate-spin mr-2" />
                        <span class="text-sm">Searching...</span>
                    </div>

                    <div
                        v-else-if="options.length === 0"
                        class="flex flex-col items-center justify-center py-6 text-muted-foreground"
                    >
                        <Search class="h-8 w-8 mb-2 opacity-50" />
                        <span class="text-sm">{{ emptyMessage }}</span>
                    </div>

                    <template v-else>
                        <button
                            v-for="(option, index) in options"
                            :key="option.id"
                            type="button"
                            role="option"
                            :data-index="index"
                            :aria-selected="option.id === modelValue"
                            class="relative flex w-full cursor-pointer select-none items-center gap-3 rounded-sm px-3 py-2 text-sm outline-none transition-colors"
                            :class="{
                                'bg-accent text-accent-foreground': index === activeIndex,
                                'hover:bg-accent hover:text-accent-foreground': index !== activeIndex,
                            }"
                            @click="selectOption(option)"
                            @mouseenter="activeIndex = index"
                        >
                            <div
                                class="h-8 w-8 rounded shrink-0 bg-muted flex items-center justify-center overflow-hidden"
                            >
                                <img
                                    v-if="option.image_url"
                                    :src="option.image_url"
                                    :alt="option.nama_produk"
                                    class="h-full w-full object-cover"
                                />
                                <Package v-else class="h-4 w-4 text-muted-foreground" />
                            </div>
                            <div class="flex flex-col items-start min-w-0 flex-1">
                                <span class="font-medium truncate">
                                    {{ option.nama_produk }}
                                </span>
                                <span class="text-xs text-muted-foreground truncate">
                                    {{ option.sku }}
                                    <template v-if="option.brand">
                                        &middot; {{ option.brand.nama_brand }}
                                    </template>
                                    <template v-if="option.kategori">
                                        &middot; {{ option.kategori.nama_kategori }}
                                    </template>
                                </span>
                            </div>
                            <span v-if="option.stok_on_hand !== undefined" class="text-xs shrink-0 ml-2" :class="option.stok_on_hand > 0 ? 'text-green-600 font-medium' : 'text-red-500'">
                                Stok: {{ option.stok_on_hand }}
                            </span>
                            <Check
                                v-if="option.id === modelValue"
                                class="ml-2 h-4 w-4 shrink-0 text-primary"
                            />
                        </button>
                    </template>
                </div>

                <div
                    v-if="!loading && options.length > 0"
                    class="border-t px-3 py-2 text-xs text-muted-foreground text-center"
                >
                    {{ options.length }} product{{ options.length !== 1 ? 's' : '' }} found
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.search-input-no-border:focus {
    outline: none !important;
    box-shadow: none !important;
    border: none !important;
}
</style>