<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const checkbox = cn(
    'peer h-4 w-4 shrink-0 rounded-sm border border-primary ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground'
)

const modelValue = defineModel<boolean>()
const props = defineProps<{ class?: string; disabled?: boolean }>()
</script>

<template>
    <div class="relative flex items-center">
        <input
            type="checkbox"
            :class="cn(checkbox, props.class)"
            v-model="modelValue"
            :disabled="disabled"
        />
    </div>
</template>
