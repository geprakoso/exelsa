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
  e.stopPropagation()
  dragCounter.value++
}

function handleDragLeave(e: DragEvent) {
  e.preventDefault()
  e.stopPropagation()
  dragCounter.value--
}

function handleDragOver(e: DragEvent) {
  e.preventDefault()
  e.stopPropagation()
}

function handleDrop(e: DragEvent) {
  e.preventDefault()
  e.stopPropagation()
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

// Handle clear all (both existing and new)
function handleClearAll() {
  if (!confirm('Yakin ingin menghapus semua gambar?')) return
  
  // Clear new previews
  clearAllPreviews()
  
  // Delete all existing images
  props.existingImages.forEach(img => {
    emit('delete', img.id)
  })
}

// Expose methods
defineExpose({
  clearAllPreviews,
  selectedFiles: () => selectedFiles.value
})
</script>

<template>
  <div 
    class="border rounded-xl overflow-hidden bg-card transition-all duration-200"
    :class="isDragging ? 'border-primary ring-2 ring-primary/20' : 'border-border'"
    @dragenter="handleDragEnter"
    @dragleave="handleDragLeave"
    @dragover="handleDragOver"
    @drop="handleDrop"
  >
    <input
      ref="fileInputRef"
      type="file"
      multiple
      accept="image/*"
      class="hidden"
      @change="handleFileSelect"
    />
    
    <!-- Header -->
    <div class="flex items-center justify-between px-4 py-3 border-b bg-muted/30">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
          <ImageIcon class="w-4 h-4 text-primary" />
        </div>
        <div>
          <h4 class="text-sm font-medium">Product Images</h4>
          <p class="text-xs text-muted-foreground">
            {{ totalImages }} of {{ maxImages }} images
          </p>
        </div>
      </div>
      
      <div class="flex items-center gap-2">
        <!-- Counter Badge -->
        <div 
          class="px-2.5 py-1 rounded-full text-xs font-medium"
          :class="totalImages >= maxImages 
            ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' 
            : 'bg-muted text-muted-foreground'"
        >
          {{ totalImages }}/{{ maxImages }}
        </div>
        
        <!-- Clear All Button -->
        <button
          v-if="totalImages > 0"
          @click="handleClearAll"
          class="text-xs text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 px-2 py-1 rounded-md transition-colors"
        >
          Clear
        </button>
      </div>
    </div>
    
    <!-- Gallery Grid -->
    <div class="p-4">
      <div 
        ref="parentRef"
        class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3"
      >
        <!-- Existing Images (Draggable) -->
        <div
          v-for="image in orderedImages"
          :key="image.id"
          class="group relative aspect-square rounded-lg overflow-hidden border bg-muted cursor-move transition-all duration-200 hover:shadow-md"
          :class="{ 
            'ring-2 ring-primary ring-offset-2 dark:ring-offset-card': image.is_primary,
            'hover:border-primary/50': !image.is_primary 
          }"
        >
          <img
            :src="image.url"
            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
            :alt="image.original_name"
          />
          
          <!-- Primary Badge -->
          <div
            v-if="image.is_primary"
            class="absolute top-2 left-2 bg-primary text-primary-foreground text-[10px] font-semibold px-2 py-0.5 rounded-full flex items-center gap-1 shadow-sm"
          >
            <Star class="w-3 h-3 fill-current" />
            Primary
          </div>
          
          <!-- Drag Handle -->
          <div class="drag-handle absolute top-2 right-2 w-6 h-6 bg-black/40 hover:bg-black/60 backdrop-blur-sm text-white rounded-md flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-200 cursor-grab active:cursor-grabbing">
            <GripVertical class="w-3.5 h-3.5" />
          </div>
          
          <!-- Hover Overlay with Actions -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-200 flex flex-col justify-end p-2">
            <!-- Action Buttons -->
            <div class="flex items-center justify-center gap-1.5 mb-1">
              <!-- Set Primary -->
              <button
                v-if="!image.is_primary"
                @click.stop="setPrimary(image.id)"
                class="p-1.5 bg-white/90 hover:bg-yellow-400 hover:text-white rounded-md transition-all duration-150"
                title="Set as primary"
              >
                <Star class="w-3.5 h-3.5 text-yellow-600" />
              </button>
              
              <!-- Delete -->
              <button
                @click.stop="deleteImage(image.id)"
                class="p-1.5 bg-white/90 hover:bg-red-500 hover:text-white rounded-md transition-all duration-150"
                title="Delete"
              >
                <Trash2 class="w-3.5 h-3.5 text-red-600" />
              </button>
            </div>
          </div>
          
          <!-- File Info (always visible at bottom) -->
          <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-1.5">
            <p class="text-white text-[9px] truncate opacity-90">{{ image.original_name }}</p>
          </div>
        </div>
        
        <!-- New Upload Previews -->
        <div
          v-for="(preview, index) in previews"
          :key="'new-'+index"
          class="group relative aspect-square rounded-lg overflow-hidden border bg-muted"
        >
          <img 
            :src="preview.url" 
            class="w-full h-full object-cover"
            :alt="preview.file.name"
          />
          
          <!-- Progress Overlay -->
          <div
            v-if="preview.status === 'pending' || preview.progress < 100"
            class="absolute inset-0 bg-black/70 backdrop-blur-[2px] flex flex-col items-center justify-center"
          >
            <Loader2 v-if="preview.progress < 100" class="w-5 h-5 text-white animate-spin mb-1.5" />
            <Check v-else class="w-5 h-5 text-green-400 mb-1.5" />
            
            <div class="w-14 bg-white/20 rounded-full h-1 overflow-hidden">
              <div 
                class="bg-primary h-full rounded-full transition-all duration-300"
                :style="{ width: Math.min(preview.progress, 100) + '%' }"
              />
            </div>
            <span class="text-white text-[10px] mt-1 font-medium">{{ Math.round(preview.progress) }}%</span>
          </div>
          
          <!-- New Badge (when complete) -->
          <div
            v-else
            class="absolute top-2 left-2 bg-green-500 text-white text-[9px] font-semibold px-1.5 py-0.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
          >
            New
          </div>
          
          <!-- Remove Button -->
          <button
            @click.stop="removePreview(index)"
            class="absolute top-2 right-2 w-6 h-6 bg-red-500/90 hover:bg-red-600 text-white rounded-md opacity-0 group-hover:opacity-100 transition-all duration-200 flex items-center justify-center"
          >
            <X class="w-3.5 h-3.5" />
          </button>
          
          <!-- File Info -->
          <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
            <p class="text-white text-[9px] truncate">{{ preview.file.name }}</p>
            <p class="text-white/70 text-[9px]">{{ formatSize(preview.file.size) }}</p>
          </div>
        </div>
        
        <!-- Upload Trigger Tile -->
        <div
          v-if="canUpload"
          @click.stop="fileInputRef?.click()"
          class="group aspect-square rounded-lg border-2 border-dashed border-muted-foreground/25 hover:border-primary/50 bg-muted/30 hover:bg-primary/5 flex flex-col items-center justify-center gap-2 cursor-pointer transition-all duration-200"
          :class="{ 'border-primary bg-primary/5': isDragging }"
        >
          <div 
            class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-200"
            :class="isDragging ? 'bg-primary text-white' : 'bg-muted group-hover:bg-primary/10'"
          >
            <Upload class="w-5 h-5" :class="isDragging ? 'text-white' : 'text-muted-foreground group-hover:text-primary'" />
          </div>
          <span class="text-[10px] text-muted-foreground group-hover:text-primary font-medium transition-colors">
            {{ isDragging ? 'Drop here' : 'Select Image' }}
          </span>
        </div>
        
        <!-- Max Reached Indicator (optional slot filler) -->
        <div
          v-else-if="totalImages >= maxImages"
          class="aspect-square rounded-lg border border-dashed border-muted-foreground/20 bg-muted/20 flex flex-col items-center justify-center gap-1.5"
        >
          <ImageIcon class="w-6 h-6 text-muted-foreground/40" />
          <span class="text-[9px] text-muted-foreground/60 text-center px-2">Max reached</span>
        </div>
      </div>
      
      <!-- Empty State (Inside Box) -->
      <div
        v-if="totalImages === 0"
        @click.stop="fileInputRef?.click()"
        class="py-10 flex flex-col items-center justify-center cursor-pointer group"
      >
        <div class="w-16 h-16 rounded-full bg-muted flex items-center justify-center mb-3 group-hover:bg-primary/10 transition-colors">
          <ImageIcon class="w-8 h-8 text-muted-foreground group-hover:text-primary transition-colors" />
        </div>
        <p class="text-sm font-medium text-muted-foreground group-hover:text-foreground transition-colors">
          No images yet
        </p>
        <p class="text-xs text-muted-foreground/70 mt-1">
          Click or drag images here to upload
        </p>
      </div>
    </div>
    
    <!-- Footer -->
    <div class="px-4 py-2.5 border-t bg-muted/30 flex items-center justify-center gap-3 text-[11px] text-muted-foreground">
      <span class="flex items-center gap-1">
        <Upload class="w-3 h-3" />
        Drop to upload
      </span>
      <span class="text-muted-foreground/30">•</span>
      <span>Max {{ maxImages }} images</span>
      <span class="text-muted-foreground/30">•</span>
      <span>5MB each</span>
      <span class="text-muted-foreground/30">•</span>
      <span class="flex items-center gap-1">
        <span class="px-1 py-0.px-1 bg-muted rounded text-[9px]">JPG</span>
        <span class="px-1 py-0.px-1 bg-muted rounded text-[9px]">PNG</span>
        <span class="px-1 py-0.px-1 bg-muted rounded text-[9px]">WEBP</span>
      </span>
    </div>
  </div>
</template>
