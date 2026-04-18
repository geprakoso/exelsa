import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createPinia } from 'pinia'
import AppLayout from './app.vue'
import './lib/utils'
import './lib/axios'
import '../css/inertia.css'

import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue')

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: async (name) => {
        console.log('[Inertia] Resolving page:', name);
        try {
            const page = await resolvePageComponent(`./pages/${name}.vue`, pages)
            console.log('[Inertia] Resolved page successfully:', page);
            page.default.layout = page.default.layout || AppLayout
            return page
        } catch (error) {
            console.error('[Inertia] Error resolving page:', error);
            throw error;
        }
    },
    setup({ el, App, props, plugin }) {
        console.log('[Inertia] Setup triggered. Element:', el);
        try {
            const pinia = createPinia()
            const vueApp = createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(pinia)
            
            console.log('[Inertia] Mounting Vue app...');
            const mountedApp = vueApp.mount(el)
            console.log('[Inertia] App mounted successfully:', mountedApp);
            return mountedApp;
        } catch (err) {
            console.error('[Inertia] Error during setup/mount:', err);
        }
    },
    progress: {
        color: '#4F46E5',
        showSpinner: true,
    },
})
