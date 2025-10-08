import './bootstrap';
import { createApp } from 'vue';
import InvoicesListComponent from './components/InvoicesListComponent.vue'
import InvoiceViewComponent from './components/InvoiceViewComponent.vue'
import InvoiceEditComponent from './components/InvoiceEditComponent.vue'

// import App from './App.vue';
// const eventBus = createApp({}); TODO: Delete

const invoicesList = createApp(InvoicesListComponent);
const invoiceView = createApp(InvoiceViewComponent);
const invoiceEdit = createApp(InvoiceEditComponent);

invoicesList.mount('invoices-list');
invoiceView.mount('invoice-view');
invoiceEdit.mount('invoice-edit');


// import ExampleComponent from './components/ExampleComponent.vue';
// app.component('example-component', ExampleComponent);

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// Object.entries(import.meta.glob('./**/*.vue', { eager: true })).forEach(([path, definition]) => {
//     app.component(path.split('/').pop().replace(/\.\w+$/, ''), definition.default);
// });

/**
 * Finally, we will attach the application instance to a HTML element with
 * an "id" attribute of "app". This element is included with the "auth"
 * scaffolding. Otherwise, you will need to add an element yourself.
 */

// app.mount('#app');
