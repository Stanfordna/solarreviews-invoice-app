<script setup>
    import { addInvoice, editInvoice } from '../api.js';
    import { watch, ref } from 'vue';
    import useEventsBus from '../eventBus.js';

    const invoice = ref([]);
    const hideInvoiceEdit = ref(true);
    const { broadcast, events } = useEventsBus();

    watch(()=>events.value.get('NEW_INVOICE'), async () => {
        console.log(`creating new invoice`);
        hideInvoiceEdit.value = false;
    })

    watch(()=>events.value.get('EDIT_INVOICE'), async (invoiceValue) => {
        try {
            invoice.value = invoiceValue;
            console.log(`editing invoice ${invoice.value.id}`);
        } catch (err) {
            console.error('Failed to load invoice on mount', err);
        }
        hideInvoiceEdit.value = false;
        console.log(invoice.value);
    })
</script>

<template>
</template>
