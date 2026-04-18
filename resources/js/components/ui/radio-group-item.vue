<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const radioGroupItem = cn(
    'aspect-square h-4 w-4 rounded-full border border-primary text-primary ring-offset-background focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50'
)

const props = defineProps<{ value: string; class?: string; disabled?: boolean }>()
</script>

<template>
    <div class="relative flex items-center">
        <input
            type="radio"
            :class="cn(radioGroupItem, props.class)"
            :value="value"
            :disabled="disabled"
        />
    </div>
</template>
