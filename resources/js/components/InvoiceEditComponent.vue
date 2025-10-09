<script setup>
    import { addInvoice, editInvoice } from '../api.js';
    import { watch, ref } from 'vue';
    import useEventsBus from '../eventBus.js';

    const invoice = ref({});
    const hideInvoiceEdit = ref(true);
    const { broadcast, events } = useEventsBus();

    function initializeNullAttributes() {
        console.log()
        invoice.value.sender_address = invoice.value.sender_address ?? {
            street: ' ',
            city: ' ',
            postal_code: ' ',
            country: ' ',
        };
        invoice.value.sender_address.street = invoice.value.sender_address.street ?? '';
        invoice.value.sender_address.city = invoice.value.sender_address.city ?? '';
        invoice.value.sender_address.postal_code = invoice.value.sender_address.postal_code ?? '';
        invoice.value.sender_address.country = invoice.value.sender_address.country ?? '';
        invoice.value.client_name = invoice.value.client_name ?? '';
        invoice.value.client_email = invoice.value.client_email ?? '';
        invoice.value.client_address = invoice.value.client_address ?? {
            street: ' ',
            city: ' ',
            postal_code: ' ',
            country: ' ',
        };
        invoice.value.client_address.street = invoice.value.client_address.street ?? '';
        invoice.value.client_address.city = invoice.value.client_address.city ?? '';
        invoice.value.client_address.postal_code = invoice.value.client_address.postal_code ?? '';
        invoice.value.client_address.country = invoice.value.client_address.country ?? '';
        invoice.value.status = invoice.value.status ?? '';
        invoice.value.issue_date = invoice.value.issue_date ?? '';
        invoice.value.payment_terms = invoice.value.payment_terms ?? '';
        invoice.value.description = invoice.value.description ?? '';
        invoice.value.item_list = invoice.value.item_list ?? [];
    }
    initializeNullAttributes();

    watch(() => events.NEW_INVOICE, () => {
        hideInvoiceEdit.value = false;
        invoice.value = {};
        initializeNullAttributes();
    });

    watch(() => events.EDIT_INVOICE, async (invoiceValue) => {
        try {
            invoice.value = invoiceValue;
            console.log(`editing invoice ${invoice.value.id}`);
        } catch (err) {
            console.error('Failed to load invoice on mount', err);
        }
        hideInvoiceEdit.value = false;
        console.log(invoice.value);
        initializeNullAttributes();
    });
</script>

<style scoped src='../../css/invoiceEdit.css'></style>

<template>
    <tinted-screen :class="{ hidden: hideInvoiceEdit }">
        <edit-invoice-background>
            <invoice-form v-if="invoice">
                <h2 v-if="invoice.id">Edit #{{ invoice.id }}</h2>
                <h2 v-else>New Invoice</h2>
                <bill-from>
                    <p>Bill From</p>
                    <from-street-label>
                        Street Address
                        <input type="text" id="from-street"
                            v-model="invoice.sender_address.street"/>
                    </from-street-label>
                    <from-city-label>
                        City
                        <input type="text" id="from-city"
                            v-model="invoice.sender_address.city"/>
                    </from-city-label>
                    <from-post-code-label>
                        Post Code
                        <input type="text" id="from-post-code"
                            v-model="invoice.sender_address.postal_code"/>
                    </from-post-code-label>
                    <from-country-label>
                        Country
                        <input type="text" id="from-country"
                            v-model="invoice.sender_address.country"/>
                    </from-country-label>
                </bill-from>
                <bill-to>
                    <p>Bill To</p>
                    <to-name-label>
                        Client's Name
                        <input type="text" id="to-name"
                            v-model="invoice.client_name"/>
                    </to-name-label>
                    <to-email-label>
                        Client's Email
                        <input type="text" id="to-email"
                            v-model="invoice.client_email"/>
                    </to-email-label>
                    <to-street-label>
                        Street Address
                        <input type="text" id="to-street"
                            v-model="invoice.client_address.street"/>
                    </to-street-label>
                    <to-city-label>
                        City
                        <input type="text" id="to-city"
                            v-model="invoice.client_address.city"/>
                    </to-city-label>
                    <to-post-code-label>
                        Post Code
                        <input type="text" id="to-post-code"
                            v-model="invoice.client_address.postal_code"/>
                    </to-post-code-label>
                    <to-country-label>
                        Country
                        <input type="text" id="to-country"
                            v-model="invoice.client_address.country"/>
                    </to-country-label>
                </bill-to>
                <invoice-details>
                    <invoice-date-label>
                        Invoice Date
                        <input type="date" id="invoice-date"
                            v-model="invoice.issue_date"/>
                    </invoice-date-label>
                    <payment-terms-label>
                        Payment Terms
                        <select v-model="invoice.payment_terms">
                            <option value=1>Due today</option>
                            <option value=7>Net 7 Days</option>
                            <option value=14>Net 14 Days</option>
                            <option value=30>Net 30 Days</option>
                            <option value=90>Net 90 Days</option>
                            <option value=180>Net 180 Days</option>
                            <option value=365>Net 365 Days</option>
                        </select>
                    </payment-terms-label>
                    <project-description-label>
                        Project Description
                        <input type="text" id="project-description"
                            v-model="invoice.description"/>
                    </project-description-label>
                </invoice-details>
            </invoice-form>
        </edit-invoice-background>
    </tinted-screen>
</template>
