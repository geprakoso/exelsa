<script setup lang="ts">
import { cn } from '@/lib/utils'
import { Link } from '@inertiajs/vue3'

const props = defineProps<{ class?: string }>()
</script>

<template>
    <Link :class="cn('block px-2 py-1.5 text-sm', props.class)" role="menuitem" tabindex="-1">
        <slot />
    </Link>
</template>
