<script setup lang="ts">
import { ref, provide, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { cn } from '@/lib/utils'
import {
    Users,
    Package,
    ShoppingCart,
    FileText,
    Settings,
    BarChart3,
    LayoutDashboard,
    ChevronRight,
    Menu,
    X,
    LogOut,
    User,
} from 'lucide-vue-next'
import Button from '@/components/ui/button.vue'

interface NavItem {
    label: string
    icon: any
    href?: string
    children?: NavItem[]
}

const props = defineProps<{
    class?: string
}>()

const isMobileMenuOpen = ref(false)

function getActiveSectionFromPath(path: string): string {
    if (path.includes('/master-data')) return 'master-data'
    if (path.includes('/transactions')) return 'transactions'
    if (path.includes('/inventory')) return 'inventory'
    if (path.includes('/akunting')) return 'akunting'
    if (path.includes('/settings') || path.includes('/users')) return 'settings'
    return 'dashboard'
}

const activeMainNav = ref(getActiveSectionFromPath(window.location.pathname))

onMounted(() => {
    router.on('navigate', (event) => {
        activeMainNav.value = getActiveSectionFromPath(event.detail.page.url)
    })
})

provide('mobileMenuOpen', isMobileMenuOpen)
provide('toggleMobileMenu', () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value
})

const mainNavigation = [
    { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, href: '/app/dashboard' },
    { id: 'master-data', label: 'Master Data', icon: Package },
    { id: 'transactions', label: 'Transaksi', icon: ShoppingCart },
    { id: 'inventory', label: 'Inventory', icon: Package },
    { id: 'akunting', label: 'Akunting', icon: FileText },
    { id: 'settings', label: 'Settings', icon: Settings },
]

const subNavigation: Record<string, NavItem[]> = {
    'dashboard': [
        { label: 'Overview', icon: BarChart3, href: '/app/dashboard' },
    ],
    'master-data': [
        { label: 'Produk', icon: Package, href: '/app/admin/master-data/produk' },
        { label: 'Brand', icon: Package, href: '/app/admin/master-data/brand' },
        { label: 'Kategori', icon: Package, href: '/app/admin/master-data/kategori' },
        { label: 'Supplier', icon: Package, href: '/app/admin/master-data/supplier' },
        { label: 'Member', icon: Users, href: '/app/admin/master-data/member' },
        { label: 'Jasa', icon: Package, href: '/app/admin/master-data/jasa' },
        { label: 'Gudang', icon: Package, href: '/app/admin/master-data/gudang' },
        { label: 'Akun Transaksi', icon: FileText, href: '/app/admin/master-data/akun-transaksi' },
    ],
    'transactions': [
        { label: 'Penjualan', icon: ShoppingCart, href: '/app/admin/transactions/penjualan' },
        { label: 'Pembelian', icon: ShoppingCart, href: '/app/admin/transactions/pembelian' },
        { label: 'Tukar Tambah', icon: ShoppingCart, href: '/app/admin/transactions/tukar-tambah' },
    ],
    'inventory': [
        { label: 'Stock Adjustment', icon: Package, href: '/app/admin/inventory/stock-adjustment' },
        { label: 'Stock Opname', icon: Package, href: '/app/admin/inventory/stock-opname' },
    ],
    'akunting': [
        { label: 'Chart of Accounts', icon: FileText, href: '/app/akunting/chart-of-accounts' },
        { label: 'Input Transaksi', icon: FileText, href: '/app/akunting/input-transaksi' },
        { label: 'Laporan Laba Rugi', icon: BarChart3, href: '/app/akunting/laporan-laba-rugi' },
        { label: 'Laporan Neraca', icon: BarChart3, href: '/app/akunting/laporan-neraca' },
    ],
    'settings': [
        { label: 'Users', icon: Users, href: '/app/admin/users' },
        { label: 'Settings', icon: Settings, href: '/app/settings' },
    ],
}

function setActiveMainNav(id: string) {
    activeMainNav.value = id
}

function isActiveHref(href?: string): boolean {
    if (!href) return false
    const pathname = window.location.pathname
    return pathname === href || pathname.startsWith(href + '/')
}
</script>

<template>
    <aside
        :class="cn(
            'fixed left-0 top-0 z-40 flex h-screen bg-card border-r transition-transform lg:translate-x-0',
            isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
            props.class
        )"
    >
        <!-- Main Navigation (Left Column) -->
        <div class="w-20 flex flex-col border-r bg-card">
            <div class="flex h-16 items-center justify-center border-b">
                <Link href="/app" class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary text-primary-foreground">
                    <Package class="h-5 w-5" />
                </Link>
            </div>
            
            <nav class="flex-1 py-4 overflow-y-auto">
                <div class="space-y-1 px-2">
                    <button
                        v-for="item in mainNavigation"
                        :key="item.id"
                        class="w-full flex flex-col items-center justify-center py-3 rounded-lg transition-colors relative group"
                        :class="[
                            activeMainNav === item.id 
                                ? 'bg-primary/10 text-primary' 
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                        ]"
                        @click="setActiveMainNav(item.id)"
                    >
                        <component :is="item.icon" class="h-5 w-5 mb-1" />
                        <span class="text-[10px] font-medium text-center leading-tight px-1">
                            {{ item.label }}
                        </span>
                        
                        <!-- Active indicator -->
                        <div 
                            v-if="activeMainNav === item.id"
                            class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-primary rounded-r-full"
                        />
                    </button>
                </div>
            </nav>
            
            <!-- User section at bottom -->
            <div class="border-t p-2">
                <Link 
                    href="/app/logout" 
                    method="post" 
                    as="button"
                    class="w-full flex flex-col items-center justify-center py-3 rounded-lg text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                >
                    <LogOut class="h-5 w-5 mb-1" />
                    <span class="text-[10px] font-medium">Logout</span>
                </Link>
            </div>
        </div>
        
        <!-- Sub Navigation (Right Column) -->
        <div 
            v-if="subNavigation[activeMainNav]"
            class="w-56 bg-card flex flex-col"
        >
            <div class="h-16 flex items-center px-4 border-b">
                <span class="text-sm font-semibold">
                    {{ mainNavigation.find(m => m.id === activeMainNav)?.label }}
                </span>
            </div>
            
            <nav class="flex-1 py-2 overflow-y-auto">
                <div class="px-2 space-y-0.5">
                    <Link
                        v-for="item in subNavigation[activeMainNav]"
                        :key="item.href"
                        :href="item.href || '#'"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors"
                        :class="[
                            isActiveHref(item.href)
                                ? 'bg-primary/10 text-primary font-medium'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                        ]"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.label }}
                        <ChevronRight v-if="item.children" class="h-4 w-4 ml-auto" />
                    </Link>
                </div>
            </nav>
        </div>
    </aside>
    
    <div
        v-if="isMobileMenuOpen"
        class="fixed inset-0 z-30 bg-black/50 lg:hidden"
        @click="isMobileMenuOpen = false"
    />
</template>
