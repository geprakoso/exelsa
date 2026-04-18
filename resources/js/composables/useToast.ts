import { ref } from 'vue'

interface Toast {
    id: string
    title?: string
    description?: string
    variant?: 'default' | 'destructive' | 'success'
    duration?: number
}

const toasts = ref<Toast[]>([])

function addToast(toast: Omit<Toast, 'id'>) {
    const id = Math.random().toString(36).slice(2, 9)
    toasts.value.push({ id, ...toast })

    setTimeout(() => {
        dismiss(id)
    }, toast.duration ?? 5000)
}

function dismiss(id: string) {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
        toasts.value.splice(index, 1)
    }
}

function toast(options: Omit<Toast, 'id'>) {
    addToast(options)
}

function toastSuccess(description: string, title?: string) {
    toast({ variant: 'success', title, description })
}

function toastError(description: string, title?: string) {
    toast({ variant: 'destructive', title: title || 'Error', description })
}

function toastInfo(description: string, title?: string) {
    toast({ variant: 'default', title, description })
}

export function useToast() {
    return {
        toasts,
        toast,
        toastSuccess,
        toastError,
        toastInfo,
        dismiss,
    }
}
