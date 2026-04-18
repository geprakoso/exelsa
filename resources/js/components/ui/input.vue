<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const inputVariants = {
    base: 'flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
    error: 'border-red-500 focus-visible:ring-red-500',
}

export interface InputProps extends /* @vue-ignore */ HTMLAttributes<HTMLInputElement> {
    error?: boolean
}

const modelValue = defineModel<string | number>()

const props = defineProps<InputProps>()

const computedClass = computed(() => {
    return cn(
        inputVariants.base,
        props.error && inputVariants.error,
        props.class
    )
})
</script>

<template>
    <input v-model="modelValue" :class="computedClass" />
</template>
