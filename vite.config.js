import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
                // Treat specific kebab-case tags as custom elements so the SFC
                // compiler does not attempt to resolve them as Vue components.
                // This mirrors the customTags list used in your runtime code.
                compilerOptions: {
                    isCustomElement: (tag) => {
                        const customTags = [
                            'heading-count',
                            'chosen-status',
                            'invoice-status',
                            'status-options',
                            'status-select',
                            'invoices-filter',
                            'add-icon',
                            'new-invoice-button',
                            'invoices-list-header',
                            'invoices-list-body',
                            'no-invoices',
                            'all-invoices',
                            'invoice-summary',
                            'status-wrapper',
                            'invoice-view-header',
                            'go-back',
                            'invoice-view-body',
                            'invoice-id',
                            'due-date',
                            'client-name',
                            'total-cents',
                            'invoice-status'
                        ];
                        return customTags.includes(tag);
                    }
                }
            },
        }),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
