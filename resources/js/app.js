import './bootstrap';
import '../css/app.css';
import '@mdi/font/css/materialdesignicons.min.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import VCalendar, { Calendar } from "v-calendar";
import "v-calendar/dist/style.css";
import mitt from 'mitt';

const appName = import.meta.env.VITE_APP_NAME || 'Arrendo';
const emmiter = mitt();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VCalendar,{})

        app.config.globalProperties.emmiter = emmiter;
        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
