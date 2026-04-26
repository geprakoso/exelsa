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
        const module = await resolvePageComponent(`./pages/${name}.vue`, pages)
        const page = (module as any).default || module
        if (!page.layout) {
            page.layout = AppLayout
        }
        return page
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia()
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
        return vueApp.mount(el)
    },
    progress: {
        color: '#4F46E5',
        showSpinner: true,
    },
})
