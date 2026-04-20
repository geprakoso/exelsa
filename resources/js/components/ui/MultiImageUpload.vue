<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { 
  Upload, 
  X, 
  Star, 
  Trash2, 
  GripVertical, 
  Loader2, 
  Check,
  Image as ImageIcon
} from 'lucide-vue-next'
import { useDragAndDrop } from '@formkit/drag-and-drop/vue'

export interface ProdukImage {
  id: number
  url: string
  original_name: string
  size: number
  is_primary: boolean
  sort_order: number
}

interface Props {
  produkId?: number
  existingImages?: ProdukImage[]
  maxImages?: number
}

const props = withDefaults(defineProps<Props>(), {
  maxImages: 10,
  existingImages: () => []
})

const emit = defineEmits<{
  'update:modelValue': [files: File[]]
  'set-primary': [imageId: number]
  'delete': [imageId: number]
  'reorder': [imageIds: number[]]
}>()

// State
const selectedFiles = ref<File[]>([])
const previews = ref<{ file: File; url: string; progress: number; status: 'pending' | 'uploading' | 'completed' | 'error' }[]>([])
const dragCounter = ref(0)
const isDragging = computed(() => dragCounter.value > 0)
const fileInputRef = ref<HTMLInputElement | null>(null)

// Drag & Drop for existing images
const [parentRef, orderedImages] = useDragAndDrop(props.existingImages, {
  group: 'produk-images',
  animation: 200,
  handle: '.drag-handle'
})

// Watch for external changes
watch(() => props.existingImages, (newVal) => {
  orderedImages.value = [...newVal]
}, { deep: true })

// Computed
const totalImages = computed(() => {
  return props.existingImages.length + selectedFiles.value.length
})

const canUpload = computed(() => totalImages.value < props.maxImages)

const remainingSlots = computed(() => props.maxImages - props.existingImages.length)

const availableSlots = computed(() => props.maxImages - totalImages.value)

// File handling
function handleFileSelect(e: Event) {
  const input = e.target as HTMLInputElement
  const files = Array.from(input.files || [])
  processFiles(files)
  input.value = '' // Reset input
}

function handleDragEnter(e: DragEvent) {
  e.preventDefault()
  dragCounter.value++
}

function handleDragLeave(e: DragEvent) {
  e.preventDefault()
  dragCounter.value--
}

function handleDrop(e: DragEvent) {
  dragCounter.value = 0
  const files = Array.from(e.dataTransfer?.files || [])
  processFiles(files)
}

function processFiles(files: File[]) {
  // Filter hanya image files
  const imageFiles = files.filter(file => file.type.startsWith('image/'))
  
  // Check max limit
  const slotsAvailable = availableSlots.value
  if (slotsAvailable <= 0) {
    alert(`Maksimal ${props.maxImages} gambar per produk`)
    return
  }
  
  const filesToAdd = imageFiles.slice(0, slotsAvailable)
  
  filesToAdd.forEach(file => {
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert(`File ${file.name} terlalu besar. Max 5MB.`)
      return
    }
    
    const reader = new FileReader()
    reader.onload = (e) => {
      previews.value.push({
        file,
        url: e.target?.result as string,
        progress: 0,
        status: 'pending'
      })
      selectedFiles.value.push(file)
      emit('update:modelValue', selectedFiles.value)
    }
    reader.readAsDataURL(file)
  })
  
  // Simulate upload progress
  simulateProgress()
}

function simulateProgress() {
  const interval = setInterval(() => {
    let allComplete = true
    previews.value.forEach(p => {
      if (p.status === 'pending' && p.progress < 90) {
        p.progress += Math.random() * 20
        if (p.progress >= 90) {
          p.progress = 100
          p.status = 'completed'
        }
        allComplete = false
      }
    })
    
    if (allComplete) {
      clearInterval(interval)
    }
  }, 150)
}

function removePreview(index: number) {
  const removed = previews.value[index]
  const fileIndex = selectedFiles.value.indexOf(removed.file)
  if (fileIndex > -1) {
    selectedFiles.value.splice(fileIndex, 1)
  }
  previews.value.splice(index, 1)
  emit('update:modelValue', selectedFiles.value)
}

function clearAllPreviews() {
  previews.value = []
  selectedFiles.value = []
  emit('update:modelValue', [])
}

// Existing image actions - emit events to parent
function setPrimary(imageId: number) {
  emit('set-primary', imageId)
}

function deleteImage(imageId: number) {
  if (!confirm('Yakin ingin menghapus gambar ini?')) return
  emit('delete', imageId)
}

function handleReorder() {
  const imageIds = orderedImages.value.map(img => img.id)
  emit('reorder', imageIds)
}

// Format file size
function formatSize(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

// Expose methods
defineExpose({
  clearAllPreviews,
  selectedFiles: () => selectedFiles.value
})
</script>

<template>
  <div class="space-y-6">
    <!-- Upload Area -->
    <div
      v-if="canUpload"
      class="relative border-2 border-dashed rounded-lg p-8 text-center transition-all duration-200 cursor-pointer"
      :class="{
        'border-primary bg-primary/5': isDragging,
        'border-gray-300 hover:border-gray-400 hover:bg-gray-50/50': !isDragging
      }"
      @dragenter.prevent="handleDragEnter"
      @dragleave.prevent="handleDragLeave"
      @dragover.prevent
      @drop.prevent="handleDrop"
      @click="fileInputRef?.click()"
    >
      <input
        ref="fileInputRef"
        type="file"
        multiple
        accept="image/*"
        class="hidden"
        @change="handleFileSelect"
      />
      <div class="space-y-3">
        <div 
          class="mx-auto w-14 h-14 rounded-full flex items-center justify-center transition-colors"
          :class="isDragging ? 'bg-primary text-white' : 'bg-muted'"
        >
          <Upload class="w-6 h-6" :class="isDragging ? 'text-white' : 'text-muted-foreground'" />
        </div>
        
        <div>
          <p class="text-sm font-medium">
            {{ isDragging ? 'Lepaskan gambar di sini' : 'Drag & drop gambar di sini' }}
          </p>
          <p class="text-xs text-muted-foreground mt-1">
            atau klik untuk memilih file
          </p>
        </div>
        
        <div class="flex items-center justify-center gap-2 text-xs text-muted-foreground">
          <span class="px-2 py-0.5 bg-muted rounded">JPG</span>
          <span class="px-2 py-0.5 bg-muted rounded">PNG</span>
          <span class="px-2 py-0.5 bg-muted rounded">WEBP</span>
        </div>
        
        <p class="text-xs text-muted-foreground">
          Maks {{ maxImages }} gambar | 5MB per file | Tersedia: {{ availableSlots }} slot
        </p>
      </div>
    </div>
    
    <!-- Max Images Reached -->
    <div
      v-else
      class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-center"
    >
      <div class="flex items-center justify-center gap-2 text-yellow-800">
        <ImageIcon class="w-5 h-5" />
        <p class="text-sm font-medium">
          Maksimal {{ maxImages }} gambar telah tercapai
        </p>
      </div>
    </div>
    
    <!-- New Images Preview -->
    <div v-if="previews.length > 0" class="space-y-3">
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-medium text-muted-foreground">
          Gambar Baru ({{ previews.length }})
        </h4>
        <button
          @click.stop="clearAllPreviews"
          class="text-xs text-red-500 hover:text-red-600 hover:underline"
        >
          Hapus Semua
        </button>
      </div>
      
      <div class="grid grid-cols-5 gap-3">
        <div
          v-for="(preview, index) in previews"
          :key="index"
          class="relative aspect-square rounded-lg overflow-hidden border group bg-muted"
        >
          <img 
            :src="preview.url" 
            class="w-full h-full object-cover"
            :alt="preview.file.name"
          />
          
          <!-- Progress Overlay -->
          <div
            v-if="preview.status === 'pending' || preview.progress < 100"
            class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center"
          >
            <Loader2 v-if="preview.progress < 100" class="w-6 h-6 text-white animate-spin mb-1" />
            <Check v-else class="w-6 h-6 text-green-400 mb-1" />
            
            <div class="w-16 bg-white/20 rounded-full h-1.5 overflow-hidden">
              <div 
                class="bg-primary h-full rounded-full transition-all duration-300"
                :style="{ width: Math.min(preview.progress, 100) + '%' }"
              />
            </div>
            <span class="text-white text-xs mt-1">{{ Math.round(preview.progress) }}%</span>
          </div>
          
          <!-- Ready Badge -->
          <div
            v-else
            class="absolute top-2 left-2 bg-green-500 text-white text-xs px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity"
          >
            Ready
          </div>
          
          <!-- Remove Button -->
          <button
            @click.stop="removePreview(index)"
            class="absolute top-2 right-2 w-6 h-6 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center hover:bg-red-600"
          >
            <X class="w-3.5 h-3.5" />
          </button>
          
          <!-- File Info -->
          <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <p class="text-white text-[10px] truncate">{{ preview.file.name }}</p>
            <p class="text-white/70 text-[10px]">{{ formatSize(preview.file.size) }}</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Existing Images (Draggable) -->
    <div v-if="existingImages.length > 0" class="space-y-3">
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-medium text-muted-foreground">
          Gambar Tersimpan (drag untuk urutkan)
        </h4>
        <span class="text-xs text-muted-foreground">
          {{ existingImages.length }} / {{ maxImages }}
        </span>
      </div>
      
      <div
        ref="parentRef"
        class="grid grid-cols-5 gap-3"
      >
        <div
          v-for="image in orderedImages"
          :key="image.id"
          class="relative aspect-square rounded-lg overflow-hidden border group cursor-move bg-muted"
          :class="{ 
            'ring-2 ring-primary ring-offset-2': image.is_primary,
            'hover:border-gray-400': !image.is_primary 
          }"
        >
          <img
            :src="image.url"
            class="w-full h-full object-cover"
            :alt="image.original_name"
          />
          
          <!-- Primary Badge -->
          <div
            v-if="image.is_primary"
            class="absolute top-2 left-2 bg-primary text-white text-[10px] font-medium px-2 py-0.5 rounded-full flex items-center gap-1"
          >
            <Star class="w-3 h-3 fill-current" />
            Utama
          </div>
          
          <!-- Drag Handle -->
          <div class="drag-handle absolute top-2 right-2 w-6 h-6 bg-black/50 text-white rounded flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-grab active:cursor-grabbing">
            <GripVertical class="w-4 h-4" />
          </div>
          
          <!-- Actions Overlay -->
          <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
            <!-- Set Primary -->
            <button
              v-if="!image.is_primary"
              @click.stop="setPrimary(image.id)"
              class="p-2 bg-white rounded-full hover:bg-yellow-50 transition-colors"
              title="Jadikan utama"
            >
              <Star class="w-4 h-4 text-yellow-600" />
            </button>
            
            <!-- Delete -->
            <button
              @click.stop="deleteImage(image.id)"
              class="p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
              title="Hapus"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
          
          <!-- File Info -->
          <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-2">
            <p class="text-white text-[10px] truncate">{{ image.original_name }}</p>
            <p class="text-white/70 text-[10px]">{{ formatSize(image.size) }}</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Empty State -->
    <div
      v-if="previews.length === 0 && existingImages.length === 0"
      class="text-center py-8 text-muted-foreground"
    >
      <ImageIcon class="w-12 h-12 mx-auto mb-3 opacity-30" />
      <p class="text-sm">Belum ada gambar</p>
      <p class="text-xs mt-1">Upload gambar produk di atas</p>
    </div>
  </div>
</template>
