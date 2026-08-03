
import '../css/app.css';
import './echo';

import axios from 'axios';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { i18nVue } from 'laravel-vue-i18n';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18nVue, {
                lang: props.initialPage.props.locale,
                resolve: async (lang) => {
                    const langs = import.meta.glob('../../lang/*.json');
                    return await langs[`../../lang/${lang}.json`]();
                },
            })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
