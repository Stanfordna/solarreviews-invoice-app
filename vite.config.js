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
                            'status-option',
                            'invoice-view-header',
                            'go-back',
                            'invoice-view-body',
                            'invoice-id',
                            'due-date',
                            'client-name',
                            'total-cents',
                            'invoice-view-body',
                            'invoice-body-top',
                            'invoice-id-description',
                            'invoice-sender-address',
                            'invoice-body-middle',
                            'invoice-dates',
                            'invoice-client-name-address',
                            'invoice-client-email',
                            'invoice-body-bottom',
                            'line-items-header',
                            'heading-item-name',
                            'heading-item-quantity',
                            'heading-item-price',
                            'heading-item-total',
                            'line-items',
                            'item-name',
                            'item-quantity',
                            'item-price',
                            'item-total',
                            'invoice-total',
                            'edit-invoice',
                            'delete-invoice',
                            'mark-as-paid',
                            'mark-as-unpaid',
                            'tinted-screen',
                            'edit-invoice-background',
                            'invoice-form',
                            'bill-from',
                            'bill-to',
                            'invoice-details',
                            'from-street-label',
                            'from-city-label',
                            'from-post-code-label',
                            'from-country-label',
                            'to-street-label',
                            'to-city-label',
                            'to-post-code-label',
                            'to-country-label',
                            'invoice-date-label',
                            'payment-terms-label',
                            'project-description-label',
                            'to-email-label',
                            'to-name-label',
                            'to-email-label',
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
