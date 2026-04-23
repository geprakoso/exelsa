<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { cn } from '@/lib/utils'
import { formatCurrency } from '@/lib/utils'

const props = defineProps<{
    modelValue: number | string | null
    placeholder?: string
    disabled?: boolean
    class?: string
    currency?: string
    decimalScale?: number
}>()

const emit = defineEmits<{
    'update:modelValue': [value: number | null]
}>()

const inputValue = ref(props.modelValue ? String(props.modelValue) : '')
const isFocused = ref(false)

watch(() => props.modelValue, (val) => {
    if (!isFocused.value) {
        inputValue.value = val ? String(val) : ''
    }
})

const displayValue = computed(() => {
    if (isFocused.value) {
        return inputValue.value
    }
    if (!inputValue.value) return ''
    const num = parseFloat(String(inputValue.value).replace(/[^\d.-]/g, ''))
    if (isNaN(num)) return ''
    return formatCurrency(num, props.currency || 'IDR')
})

function handleInput(e: Event) {
    const target = e.target as HTMLInputElement
    // Hanya ambil angka
    const rawValue = target.value.replace(/[^\d]/g, '')
    inputValue.value = rawValue
    emit('update:modelValue', rawValue ? parseFloat(rawValue) : null)
}

function handleBlur() {
    isFocused.value = false
}

function handleFocus() {
    isFocused.value = true
}
</script>

<template>
    <div :class="cn('relative', props.class)">
        <input
            :value="displayValue"
            :placeholder="placeholder"
            :disabled="disabled"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 pr-16 text-sm font-mono ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            @input="handleInput"
            @focus="handleFocus"
            @blur="handleBlur"
        />
        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
            {{ currency || 'IDR' }}
        </span>
    </div>
</template>
