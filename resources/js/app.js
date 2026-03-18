import './bootstrap'
import '../css/app.css'

import { createApp, h } from 'vue'
import { createInertiaApp, useForm, router, usePage } from '@inertiajs/vue3'
// import vuetify from './plugins/vuetify'
import toast from './plugins/toast'
import { setupPrimeVue } from './plugins/primevue'
import socket from './plugins/socket'
import apexcharts from './plugins/apexcharts'
import autocounter from './plugins/autocounter'
import emitter from './plugins/mitt'
import veevalidate from './plugins/vee-validate'
import custom from './plugins/custom'

import Helper from './helper'
import Store from './store';

import './plugins/swal'
import './plugins/vform'

import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel'

createInertiaApp({
    title: (title) => `${(title) ? title+' - ' : ''}${appName}`,
    // resolve: (name) => require(`./pages/${name}.vue`),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })

        app.config.globalProperties.$route = route
        app.config.globalProperties.router = router
        app.config.globalProperties.$socket = socket
        app.config.globalProperties.emitter = emitter
        app.config.globalProperties.$inertiaForm = useForm
        app.config.globalProperties.$inertiaPage = usePage

        // app.config.globalProperties.$form = veevalidate.config

        app.use(Store);
        app.use(plugin)
        // app.use(vuetify)
        app.use(toast)
        app.use(apexcharts)

        app.component('v-autocounter', (autocounter))

        //register veevalidate
        veevalidate.components.forEach(el => { app.component(el.name, el.val) })
        //register veevalidate

        // Register PrimeVue with theme + components/directives/services.
        setupPrimeVue(app)

        //register custom
        custom.components.forEach(el => { app.component(el.name, el.val) })
        //register custom

        app.mixin(Helper)
        app.mount(el)
    },
    progress: {
        color: '#4CAF50',
    },
})
