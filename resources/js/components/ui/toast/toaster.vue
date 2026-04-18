<script setup lang="ts">
import { cn } from '@/lib/utils'
import { useToast } from '@/composables/useToast'
import Toast from '@/components/ui/toast.vue'

const { toasts, dismiss } = useToast()

const props = defineProps<{ class?: string }>()
</script>

<template>
    <div :class="cn('fixed bottom-0 right-0 z-[100] flex max-h-screen w-full flex-col-reverse p-4 sm:bottom-0 sm:right-0 sm:top-auto sm:flex-col md:max-w-[420px]', props.class)">
        <Toast
            v-for="toast in toasts"
            :key="toast.id"
            :variant="toast.variant"
            @click="dismiss(toast.id)"
        >
            <div class="flex flex-col gap-1">
                <div class="text-sm font-semibold" v-if="toast.title">
                    {{ toast.title }}
                </div>
                <div class="text-sm opacity-90" v-if="toast.description">
                    {{ toast.description }}
                </div>
            </div>
        </Toast>
    </div>
</template>
