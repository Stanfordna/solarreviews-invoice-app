<script setup>
    import { addInvoice, editInvoice } from '../api.js';
    import { watch, ref } from 'vue';
    import useEventsBus from '../eventBus.js';

    const invoice = ref({});
    const userMessage = ref('');
    const isNew = ref(false);
    const isEdit = ref(false);
    const hideInvoiceEdit = ref(true);
    const { broadcast, events } = useEventsBus();

    function initializeNullAttributes() {
        console.log("initializing attributes");
        console.log("Current invoice");
        console.log(invoice);
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
        invoice.value.line_items = invoice.value.line_items ?? [];
        console.log(`current line items: ${JSON.stringify(invoice.value.line_items)}`)
        // Ensure we iterate the array length and normalize each line item
        for (let i = 0; i < invoice.value.line_items.length; i++) {
            const item = invoice.value.line_items[i] = invoice.value.line_items[i] ?? {};
            // ensure an id exists
            item.id = item.id ?? i;
            // normalize cents -> decimal for display
            item.price_unit_cents = item.price_unit_cents ?? 0;
            item.quantity = item.quantity ?? 0;
            item.price_unit_decimal = Number((item.price_unit_cents / 100).toFixed(2));
        }
    }
    initializeNullAttributes();

    function addLineItem() {
        let lineId = 0;
        if (invoice.value.line_items.at(-1)) {
            lineId = invoice.value.line_items.at(-1).id + 1;
        }
        const lineItem = {
            id: lineId,
            name: "",
            quantity: 0,
            price_unit_cents: 0,
            price_unit_decimal: 0,
        }
        invoice.value.line_items.push(lineItem);
    }

    function deleteLineItem(item) {
        console.log(`line item id: ${item.id}`);
        let index = invoice.value.line_items.findIndex(line_item => line_item.id === item.id);
        // If the item is found, remove it from the array
        console.log(`line item index: ${index}`);
        if (index !== -1) {
            console.log(`deleting ${invoice.value.line_items[index].name}`);
            invoice.value.line_items.splice(index, 1);
        }
    }

    function delayThenHideAndBroadcast(delay = 2500, event = 'VIEW_ALL_INVOICES', payload = null) {
        setTimeout(() => {
            hideInvoiceEdit.value = true;
            broadcast(event, payload);
        }, delay);
    }

    function trimInputs() {
        const inputs = document.querySelectorAll('input[type="text"]');
        inputs.forEach(input => {
            input.value = input.value.trim();
        });
    }

    watch(() => events.NEW_INVOICE, () => {
        console.log('NEW_INVOICE');
        isNew.value = true;
        isEdit.value = false;
        userMessage.value = '';
        invoice.value = {};
        initializeNullAttributes();
        hideInvoiceEdit.value = false;
    });

    watch(() => events.EDIT_INVOICE, async (invoiceValueRef) => {
        console.log('EDIT_INVOICE');
        try {
            // invoice.value = JSON.parse(JSON.stringify(invoiceValueRef))
            invoice.value = invoiceValueRef;
            console.log(`editing invoice ${invoice.value.id}`);
        } catch (err) {
            console.error('Failed to load invoice on mount', err);
        }
        isNew.value = false;
        isEdit.value = true;
        userMessage.value = '';
        initializeNullAttributes();
        setTimeout(() => {
            hideInvoiceEdit.value = false;
        }, 100)
    });
</script>

<style scoped src='../../css/invoiceEdit.css'></style>

<template>
    <tinted-screen @click.self="hideInvoiceEdit = true;" :class="{ 'hidden': hideInvoiceEdit } ">
        <edit-invoice-background>
            <invoice-form v-if="invoice">
                <h2 v-if="invoice.id">Edit #{{ invoice.id }}</h2>
                <h2 v-else>New Invoice</h2>
                <p v-if="userMessage" v-html="userMessage"></p>
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
                <line-items-section>
                    <line-items-header>
                        <h2>Item List</h2>
                        <heading-item-name>
                            Item Name
                        </heading-item-name>
                        <heading-item-quantity>
                            Qty
                        </heading-item-quantity>
                        <heading-item-price>
                            Price
                        </heading-item-price>
                        <heading-item-total>
                            Total
                        </heading-item-total>
                    </line-items-header>
                    <line-item v-for="line_item of invoice.line_items">
                        <item-name >
                            <input type="text" v-model="line_item.name"/>
                        </item-name>
                        <item-quantity>
                            <input type="number" step="1" min="0"
                                v-model="line_item.quantity"/>
                        </item-quantity>
                        <item-price>
                            <input type="number" step="0.01" min="0"
                                v-model="line_item.price_unit_decimal"
                                @blur="line_item.price_unit_cents = Math.round(line_item.price_unit_decimal * 100)"/>
                        </item-price>
                        <item-total>
                            {{ (line_item.price_unit_cents * line_item.quantity / 100).toFixed(2) }}
                        </item-total>
                        <trash-button @click="deleteLineItem(line_item)">
                            <img src="/icons/icon-delete.svg"></img>
                        </trash-button>
                    </line-item>
                    <add-line-item-button @click="addLineItem">
                        <img src="/icons/icon-plus.svg"></img>
                        <h4>Add New Item</h4>
                    </add-line-item-button>
                </line-items-section>
            </invoice-form>
            <invoice-form-buttons>
                <new-invoice-buttons :class="{ 'hidden': isEdit }">
                    <discard-invoice @click="hideInvoiceEdit = true;">
                        <h5 class='body-text-alt'>
                            Discard
                        </h5 class='body-text-alt'>
                    </discard-invoice>
                    <save-draft @click="invoice.status = 'draft';
                                            trimInputs();
                                            addInvoice(invoice).then(
                                                ([success, message, id]) => { 
                                                if (success) {
                                                    invoice.id = id;
                                                    userMessage = message;
                                                    delayThenHideAndBroadcast(2000, 'VIEW_ALL_INVOICES');
                                                } else {
                                                    userMessage = message; // error message
                                                }
                                            });">
                        <h5 class='body-text-alt'>
                            Save as Draft
                        </h5 class='body-text-alt'>
                    </save-draft>
                    <save-n-send @click="invoice.status = 'pending';
                                            trimInputs();
                                            addInvoice(invoice).then(
                                                ([success, message, id]) => { 
                                                if (success) {
                                                    invoice.id = id;
                                                    userMessage = message;
                                                    delayThenHideAndBroadcast(2000, 'VIEW_ALL_INVOICES');
                                                } else {
                                                    userMessage = message; // error message
                                                }
                                            });">
                        <h5 class='body-text-alt'>
                            Save & Send
                        </h5 class='body-text-alt'>
                    </save-n-send>
                </new-invoice-buttons>
                <edit-invoice-buttons :class="{ 'hidden': isNew }">
                    <cancel-changes @click="hideInvoiceEdit = true;">
                        <h5 class='body-text-alt'>
                            Cancel
                        </h5 class='body-text-alt'>
                    </cancel-changes>
                    <save-changes @click="trimInputs();
                                        editInvoice(invoice.id, invoice).then(
                                            ([success, message]) => { 
                                            if (success) {
                                                userMessage = message;
                                                delayThenHideAndBroadcast(2000, 'VIEW_INVOICE', invoice.id);
                                            } else {
                                                userMessage = message; // error message
                                            }
                                        });">
                        <h5 class='body-text-alt'>
                            Save Changes
                        </h5 class='body-text-alt'>
                    </save-changes>
                </edit-invoice-buttons>
            </invoice-form-buttons>
        </edit-invoice-background>
    </tinted-screen>
</template>