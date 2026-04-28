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


provide('mobileMenuOpen', isMobileMenuOpen)
provide('toggleMobileMenu', () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value
})

const mainNavigation = [
    { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, href: '/app/dashboard' },
    { id: 'penjualan', label: 'Sales', icon: Package, href: '/app/admin/penjualan' },
    { id: 'pembelian', label: 'Purchase', icon: Package, href: '/app/admin/pembelian' },
    { id: 'inventory', label: 'Inventory', icon: Package },
    { id: 'catalog', label: 'Catalog', icon: Package },
    { id: 'akunting', label: 'Akunting', icon: FileText },
    { id: 'finances', label: 'Finances', icon: FileText },
    { id: 'user-management', label: 'User Management', icon: Users },
    { id: 'settings', label: 'Settings', icon: Settings },
]

const subNavigation: Record<string, NavItem[]> = {
    'dashboard': [
        { label: 'Overview', icon: BarChart3, href: '/app/dashboard' },
    ],
//    'master-data': [
//        { label: 'Produk', icon: Package, href: '/app/admin/master-data/produk' }, // Penambahan produk akan dilebur ke inventory
//        { label: 'Jasa', icon: Package, href: '/app/admin/master-data/jasa' }, // Jasa akan dilbeur sebagai produk ( produk fisik, jasa)
//    ],
    'penjualan': [
        { label: 'Sales Order', icon: ShoppingCart, href: '/app/admin/transactions/penjualan' },
        { label: 'Trade-In', icon: ShoppingCart, href: '/app/admin/transactions/tukar-tambah' },
        { label: 'Customer', icon: Users, href: '/app/admin/master-data/member' },
        { label: 'Sales Return', icon: Users, href: '#' },
    ],
    'pembelian': [
        { label: 'Purchasing Order', icon: ShoppingCart, href: '/app/admin/transactions/pembelian' },
        { label: 'Supplier', icon: Package, href: '/app/admin/master-data/supplier' },
        { label: 'Purchasing Return', icon: Package, href: '#' },
    ],
    'inventory': [
        { label: 'Products', icon: Package, href: '/app/admin/inventory/products' },
        { label: 'Brand', icon: Package, href: '/app/admin/master-data/brand' },
        { label: 'Kategori', icon: Package, href: '/app/admin/master-data/kategori' },
        { label: 'Warehouse', icon: Package, href: '/app/admin/master-data/gudang' },
        { label: 'Stock Adjustment', icon: Package, href: '/app/admin/inventory/stock-adjustment' },
        { label: 'Stock Opname', icon: Package, href: '/app/admin/inventory/stock-opname' },
        { label: 'Stock Movement', icon: Package, href: '#' },
    ],
    'akunting': [
        { label: 'Chart of Accounts', icon: FileText, href: '/app/akunting/chart-of-accounts' },
        { label: 'Journal Entries', icon: FileText, href: '/app/akunting/input-transaksi' },
        { label: 'Profit & Loss', icon: BarChart3, href: '/app/akunting/laporan-laba-rugi' },
        { label: 'Balance Sheet', icon: BarChart3, href: '#' },
        { label: 'Cash & Bank', icon: FileText, href: '#' },
        { label: 'Expenses', icon: FileText, href: '#' },
        { label: 'Laporan Neraca', icon: BarChart3, href: '/app/akunting/laporan-neraca' },
    ],
    'finances': [
        { label: 'Invoices', icon: FileText, href: '#' },
        { label: 'Payments', icon: FileText, href: '#' },
        { label: 'Receivable', icon: FileText, href: '#' },
        { label: 'Payable', icon: FileText, href: '#' },
    ],
    'user-management': [
        { label: 'Users', icon: Users, href: '/app/admin/users'},
        { label: 'Roles & Permissions', icon: Users, href: '#'}
    ],
    'settings': [
        { label: 'Settings', icon: Settings, href: '/app/settings' },
        { label: 'Company Profile', icon: Package, href: '#' },
        { label: 'Tax', icon: Package, href: '#' },
    ],
}

function getActiveSectionFromPath(path: string): string {
    // First, check if the exact path or a subpath matches any item in subNavigation
    for (const [section, items] of Object.entries(subNavigation)) {
        for (const item of items) {
            if (item.href && (path === item.href || path.startsWith(item.href + '/'))) {
                return section
            }
        }
    }
    
    // Fallbacks if not found explicitly in subNavigation
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
        // use event.detail.page.url which might be just pathname or include query params. We want pathname.
        const url = new URL(event.detail.page.url, window.location.origin)
        activeMainNav.value = getActiveSectionFromPath(url.pathname)
    })
})

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
