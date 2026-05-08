<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import { X } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import Button from './button.vue'

const props = defineProps<{
    open?: boolean
    class?: string
    side?: 'left' | 'right'
    title?: string
    size?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
}>()

const isOpen = ref(props.open ?? false)
const side = computed(() => props.side ?? 'right')
const maxWidthClass = computed(() => {
    const map: Record<string, string> = {
        sm: 'max-w-sm',
        md: 'max-w-lg',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
        full: 'max-w-full',
    }
    return map[props.size ?? 'md']
})

watch(() => props.open, (val) => {
    isOpen.value = val ?? false
})

const slideDirection = computed(() => {
    if (side.value === 'left') {
        return {
            enter: 'translate-x-[-100%]',
            leave: 'translate-x-[-100%]'
        }
    }
    return {
        enter: 'translate-x-full',
        leave: 'translate-x-full'
    }
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
                class="fixed inset-0 bg-black/50 z-40"
                @click="close"
            />
        </Transition>
        <Transition
            enter-active-class="transition duration-300 ease-out"
            :enter-from-class="slideDirection.enter"
            enter-to-class="translate-x-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-x-0"
            :leave-to-class="slideDirection.leave"
        >
            <div
                v-if="isOpen"
                :class="cn(
                    'fixed top-0 z-50 grid w-full h-full border-l bg-background shadow-lg overflow-y-auto',
                    maxWidthClass,
                    side === 'left' ? 'left-0' : 'right-0',
                    props.class
                )"
            >
                <div class="sticky top-0 z-10 border-b bg-background px-6 py-4 flex items-center justify-between">
                    <h2 v-if="title" class="text-lg font-semibold">{{ title }}</h2>
                    <Button
                        variant="ghost"
                        size="icon"
                        @click="close"
                    >
                        <X class="h-4 w-4" />
                    </Button>
                </div>
                <div class="px-6 py-4">
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
