<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const tabsTrigger = cn(
    'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm'
)

const props = defineProps<{ value: string; class?: string; disabled?: boolean }>()
</script>

<template>
    <button
        :class="cn(tabsTrigger, props.class)"
        :data-state="active"
        :data-orientation="'vertical'"
        :disabled="disabled"
        role="tab"
        type="button"
    >
        <slot />
    </button>
</template>
