<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const tabsList = cn(
    'inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground'
)

const props = defineProps<{ class?: string }>()
</script>

<template>
    <div
        :class="cn(tabsList, props.class)"
        data-orientation="vertical"
        role="tablist"
    >
        <slot />
    </div>
</template>
