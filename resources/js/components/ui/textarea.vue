<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

export interface TextareaProps extends /* @vue-ignore */ HTMLAttributes<HTMLTextAreaElement> {
    error?: boolean
}

const modelValue = defineModel<string | number>()

const props = defineProps<TextareaProps>()

const computedClass = computed(() => {
    return cn(
        'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        props.error && 'border-red-500 focus-visible:ring-red-500',
        props.class
    )
})
</script>

<template>
    <textarea v-model="modelValue" :class="computedClass" />
</template>
