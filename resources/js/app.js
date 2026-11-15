import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { setBasePath } from '@/lib/url';

const pages = import.meta.glob('@/pages/**/*.vue');

/** Nama aplikasi untuk akhiran judul tab, diisi dari shared prop `appName`. */
let appName = 'Aplikasi';

void createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    progress: {
        delay: 150,
        color: '#570df8',
        includeCSS: true,
        showSpinner: false,
    },
    resolve: async (name) => {
        const path = Object.keys(pages).find((path) => path.endsWith(`/pages/${name}.vue`));

        if (!path) {
            throw new Error(`Page not found: ${name}`);
        }

        return pages[path]();
    },
    setup({ el, App, props, plugin }) {
        appName = props.initialPage.props.appName ?? appName;
        setBasePath(props.initialPage.props.basePath ?? '');

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});