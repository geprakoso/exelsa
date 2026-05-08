<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Card from '@/components/ui/card.vue'
import PenjualanForm from '@/components/forms/PenjualanForm.vue'
import { ArrowLeft } from 'lucide-vue-next'

function onSaved() {
    window.location.href = '/app/admin/transactions/penjualan'
}
</script>

<template>
    <AppLayout>
        <div class="flex-1 space-y-6 p-6">
            <PageHeader
                title="New Transaction"
                description="Create a new sales transaction."
                :breadcrumbs="[
                    { label: 'Transactions', href: '/app/admin/transactions/penjualan' },
                    { label: 'New Transaction' }
                ]"
            >
                <template #actions>
                    <Link href="/app/admin/transactions/penjualan">
                        <Button variant="outline">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Cancel
                        </Button>
                    </Link>
                </template>
            </PageHeader>
            
            <Card class="p-6">
                <PenjualanForm @saved="onSaved" />
            </Card>
        </div>
    </AppLayout>
</template>
