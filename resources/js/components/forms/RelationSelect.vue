<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { 
  Check, 
  ChevronsUpDown, 
  Search, 
  X,
  Loader2,
  Building2,
  Tags
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'

export interface SelectOption {
  label: string
  value: string | number
  description?: string
  disabled?: boolean
}

interface Props {
  modelValue?: string | number | null
  options: SelectOption[]
  placeholder?: string
  searchPlaceholder?: string
  emptyMessage?: string
  disabled?: boolean
  loading?: boolean
  clearable?: boolean
  icon?: 'building' | 'tag' | null
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select an option...',
  searchPlaceholder: 'Search...',
  emptyMessage: 'No results found.',
  clearable: true,
  icon: null
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number | null]
  'search': [query: string]
}>()

// Refs
const isOpen = ref(false)
const searchQuery = ref('')
const triggerRef = ref<HTMLButtonElement | null>(null)
const dropdownRef = ref<HTMLDivElement | null>(null)
const searchInputRef = ref<HTMLInputElement | null>(null)
const activeIndex = ref(-1)

// Dropdown positioning
const dropdownPosition = ref({
  top: 0,
  left: 0,
  width: 0
})

// Computed
const selectedOption = computed(() => {
  if (!props.modelValue) return null
  return props.options.find(opt => opt.value === props.modelValue)
})

const filteredOptions = computed(() => {
  if (!searchQuery.value.trim()) return props.options
  
  const query = searchQuery.value.toLowerCase()
  return props.options.filter(opt => 
    opt.label.toLowerCase().includes(query) ||
    opt.description?.toLowerCase().includes(query)
  )
})

const hasSelection = computed(() => props.modelValue !== null && props.modelValue !== undefined)

// Methods
function updatePosition() {
  if (!triggerRef.value) return
  
  const rect = triggerRef.value.getBoundingClientRect()
  const viewportHeight = window.innerHeight
  const spaceBelow = viewportHeight - rect.bottom
  const dropdownHeight = Math.min(320, filteredOptions.value.length * 44 + 60)
  
  // Check if should open above
  const openAbove = spaceBelow < dropdownHeight && rect.top > dropdownHeight
  
  dropdownPosition.value = {
    top: openAbove ? rect.top - dropdownHeight : rect.bottom + 4,
    left: rect.left,
    width: rect.width
  }
}

function openDropdown() {
  if (props.disabled || props.loading) return
  
  isOpen.value = true
  activeIndex.value = -1
  
  nextTick(() => {
    updatePosition()
    searchInputRef.value?.focus()
    
    // Set active index to selected item
    if (selectedOption.value) {
      const index = filteredOptions.value.findIndex(opt => opt.value === selectedOption.value?.value)
      if (index >= 0) activeIndex.value = index
    }
  })
  
  // Add event listeners
  window.addEventListener('scroll', updatePosition, true)
  window.addEventListener('resize', updatePosition)
}

function closeDropdown() {
  isOpen.value = false
  searchQuery.value = ''
  activeIndex.value = -1
  
  window.removeEventListener('scroll', updatePosition, true)
  window.removeEventListener('resize', updatePosition)
}

function toggleDropdown() {
  if (isOpen.value) {
    closeDropdown()
  } else {
    openDropdown()
  }
}

function selectOption(option: SelectOption) {
  if (option.disabled) return
  
  emit('update:modelValue', option.value)
  closeDropdown()
}

function clearSelection(e: MouseEvent) {
  e.stopPropagation()
  emit('update:modelValue', null)
  searchQuery.value = ''
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
      activeIndex.value = Math.min(activeIndex.value + 1, filteredOptions.value.length - 1)
      scrollToActive()
      break
    case 'ArrowUp':
      e.preventDefault()
      activeIndex.value = Math.max(activeIndex.value - 1, 0)
      scrollToActive()
      break
    case 'Enter':
      e.preventDefault()
      if (activeIndex.value >= 0 && filteredOptions.value[activeIndex.value]) {
        selectOption(filteredOptions.value[activeIndex.value])
      }
      break
    case 'Tab':
      closeDropdown()
      break
  }
}

function scrollToActive() {
  nextTick(() => {
    const activeElement = dropdownRef.value?.querySelector(`[data-index="${activeIndex.value}"]`)
    activeElement?.scrollIntoView({ block: 'nearest' })
  })
}

// Watch for external changes
watch(() => props.modelValue, () => {
  searchQuery.value = ''
})

// Expose for parent
defineExpose({
  focus: () => triggerRef.value?.focus(),
  open: openDropdown,
  close: closeDropdown
})
</script>

<template>
  <div :class="cn('relative w-full', props.class)">
    <!-- Trigger Button -->
    <button
      ref="triggerRef"
      type="button"
      role="combobox"
      :aria-expanded="isOpen"
      :aria-controls="isOpen ? 'select-dropdown' : undefined"
      :disabled="disabled || loading"
      class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-colors hover:bg-accent/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
      :class="{
        'border-ring ring-2 ring-ring ring-offset-2': isOpen
      }"
      @click="toggleDropdown"
      @keydown="handleKeydown"
    >
      <!-- Left: Icon + Label -->
      <span class="flex items-center gap-2 min-w-0 flex-1">
        <!-- Icon based on type -->
        <Building2 
          v-if="icon === 'building'" 
          class="h-4 w-4 shrink-0 text-muted-foreground" 
        />
        <Tags 
          v-else-if="icon === 'tag'" 
          class="h-4 w-4 shrink-0 text-muted-foreground" 
        />
        
        <!-- Selected Label or Placeholder -->
        <span 
          v-if="selectedOption" 
          class="truncate font-medium"
        >
          {{ selectedOption.label }}
        </span>
        <span 
          v-else 
          class="text-muted-foreground truncate"
        >
          {{ placeholder }}
        </span>
      </span>
      
      <!-- Right: Actions -->
      <span class="flex items-center gap-1 shrink-0 ml-2">
        <!-- Clear Button -->
        <button
          v-if="clearable && hasSelection && !disabled && !loading"
          type="button"
          class="inline-flex h-5 w-5 items-center justify-center rounded-md hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
          @click="clearSelection"
          tabindex="-1"
        >
          <X class="h-3.5 w-3.5" />
        </button>
        
        <!-- Separator -->
        <span 
          v-if="clearable && hasSelection && !disabled && !loading" 
          class="h-4 w-[1px] bg-border mx-1"
        />
        
        <!-- Loading or Chevron -->
        <Loader2 
          v-if="loading" 
          class="h-4 w-4 animate-spin text-muted-foreground" 
        />
        <ChevronsUpDown 
          v-else 
          class="h-4 w-4 text-muted-foreground transition-transform duration-200"
          :class="{ 'rotate-180': isOpen }"
        />
      </span>
    </button>

    <!-- Dropdown Portal -->
    <Teleport to="body">
      <!-- Backdrop -->
      <div
        v-if="isOpen"
        class="fixed inset-0 z-40 bg-transparent"
        @click="closeDropdown"
      />
      
      <!-- Dropdown -->
      <div
        v-if="isOpen"
        id="select-dropdown"
        ref="dropdownRef"
        class="fixed z-50 rounded-md border bg-popover text-popover-foreground shadow-md outline-none animate-in fade-in-0 zoom-in-95"
        :style="{
          top: `${dropdownPosition.top}px`,
          left: `${dropdownPosition.left}px`,
          width: `${dropdownPosition.width}px`
        }"
      >
        <!-- Search Input -->
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
          <!-- Clear search button -->
          <button
            v-if="searchQuery"
            type="button"
            class="inline-flex h-5 w-5 items-center justify-center rounded-md hover:bg-muted text-muted-foreground"
            @click="searchQuery = ''"
          >
            <X class="h-3 w-3" />
          </button>
        </div>

        <!-- Options List -->
        <div 
          class="max-h-[300px] overflow-auto p-1"
          role="listbox"
        >
          <!-- Loading State -->
          <div 
            v-if="loading" 
            class="flex items-center justify-center py-8 text-muted-foreground"
          >
            <Loader2 class="h-5 w-5 animate-spin mr-2" />
            <span class="text-sm">Loading...</span>
          </div>
          
          <!-- Empty State -->
          <div 
            v-else-if="filteredOptions.length === 0" 
            class="flex flex-col items-center justify-center py-8 text-muted-foreground"
          >
            <Search class="h-8 w-8 mb-2 opacity-50" />
            <span class="text-sm">{{ emptyMessage }}</span>
          </div>
          
          <!-- Options -->
          <template v-else>
            <button
              v-for="(option, index) in filteredOptions"
              :key="option.value"
              type="button"
              role="option"
              :data-index="index"
              :aria-selected="option.value === modelValue"
              :disabled="option.disabled"
              class="relative flex w-full cursor-pointer select-none items-center justify-between rounded-sm px-3 py-2 text-sm outline-none transition-colors"
              :class="{
                'bg-accent text-accent-foreground': index === activeIndex,
                'opacity-50 cursor-not-allowed': option.disabled,
                'hover:bg-accent hover:text-accent-foreground': !option.disabled && index !== activeIndex
              }"
              @click="selectOption(option)"
              @mouseenter="activeIndex = index"
            >
              <div class="flex flex-col items-start min-w-0 flex-1">
                <span class="font-medium truncate">
                  {{ option.label }}
                </span>
                <span 
                  v-if="option.description" 
                  class="text-xs text-muted-foreground truncate"
                >
                  {{ option.description }}
                </span>
              </div>
              
              <!-- Checkmark for selected -->
              <Check 
                v-if="option.value === modelValue" 
                class="ml-2 h-4 w-4 shrink-0 text-primary"
              />
            </button>
          </template>
        </div>
        
        <!-- Footer with count -->
        <div 
          v-if="!loading && filteredOptions.length > 0" 
          class="border-t px-3 py-2 text-xs text-muted-foreground text-center"
        >
          {{ filteredOptions.length }} of {{ options.length }} items
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* Remove default browser focus styles from search input */
.search-input-no-border:focus {
  outline: none !important;
  box-shadow: none !important;
  border: none !important;
}
</style>
