<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Toaster from '@/components/ui/toast/toaster.vue'

const isDark = ref(false)

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark')
})
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <Toaster />
        <slot />
    </div>
</template>
