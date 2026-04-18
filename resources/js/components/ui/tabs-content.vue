<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const tabsContent = cn(
    'mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2'
)

const props = defineProps<{ value: string; class?: string }>()
</script>

<template>
    <div
        :class="cn(tabsContent, props.class)"
        :data-state="active"
        :data-orientation="'vertical'"
        role="tabpanel"
    >
        <slot />
    </div>
</template>
