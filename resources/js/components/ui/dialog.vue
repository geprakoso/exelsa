<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed, type HTMLAttributes } from 'vue'
import { X } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import Button from './button.vue'

const props = defineProps<{
    open?: boolean
    class?: string
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
}>()

const isOpen = ref(props.open ?? false)

watch(() => props.open, (val) => {
    isOpen.value = val ?? false
})

// Extract z-index class from props.class
const zIndexClass = computed(() => {
    if (!props.class) return 'z-50'
    // Match z-[number] or z-10, z-20, etc.
    const match = props.class.match(/z-\[(\d+)\]|z-\d+/)
    return match ? match[0] : 'z-50'
})

function close() {
    isOpen.value = false
    emit('update:open', false)
}

function handleEscape(e: KeyboardEvent) {
    if (e.key === 'Escape') close()
}

onMounted(() => {
    document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape)
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="isOpen" 
                class="fixed inset-0 bg-black/80" 
                :class="zIndexClass"
                @click="close" 
            />
        </Transition>
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div 
                v-if="isOpen" 
                :class="cn(
                    'fixed left-1/2 top-1/2 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 border bg-background p-6 shadow-lg sm:rounded-lg',
                    zIndexClass,
                    props.class
                )"
            >
                <slot />
                <Button
                    variant="ghost"
                    size="icon"
                    class="absolute right-4 top-4"
                    @click="close"
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>
        </Transition>
    </Teleport>
</template>
