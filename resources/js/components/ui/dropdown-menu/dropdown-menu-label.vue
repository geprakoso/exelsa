<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
    <div :class="cn('py-1.5 px-2 text-sm font-semibold', props.class)">
        <slot />
    </div>
</template>
