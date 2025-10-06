import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { createPinia } from "pinia";
// import Vue3Toastify from "vue3-toastify";
// import VueApexCharts from "vue3-apexcharts";

// import "vue3-toastify/dist/index.css";
// import { vTippy } from './directives/tippy';

// Глобальный флаг для отслеживания первой загрузки
window.isFirstPageLoad = true;

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            // .use(Vue3Toastify, {
            //     theme: "dark",
            //     hideProgressBar: true,
            //     transition: "flip",
            //     autoClose: 2000,
            // })
            .use(createPinia());

        // app.component('apexchart', VueApexCharts);
        // app.directive('tippy', vTippy);

        return app.mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
