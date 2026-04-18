<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const radioGroup = cn('grid gap-2')

const props = defineProps<{ class?: string }>()
</script>

<template>
    <div :class="cn(radioGroup, props.class)" role="radiogroup">
        <slot />
    </div>
</template>
