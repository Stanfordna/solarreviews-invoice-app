<script setup>
    import { fetchInvoice, deleteInvoice } from '../api.js';
    import { watch, ref } from 'vue';
    import useEventsBus from '../eventBus.js';

    const invoice = ref([]);
    const hideInvoiceView = ref(true);
    const { broadcast, events } = useEventsBus();

    watch(() => events.VIEW_INVOICE, async (id) => {
        try {
            console.log(`attempting to fetch ${id}`);
            invoice.value = await fetchInvoice(id);
        } catch (err) {
            console.error('Failed to load invoice on mount', err);
        }
        hideInvoiceView.value = false;
        console.log(invoice.value);
    })
</script>


<style scoped src='../../css/invoiceView.css'></style>
<template>
    <go-back :class="{ hidden: hideInvoiceView }" @click="hideInvoiceView = true;;
                        broadcast('VIEW_ALL_INVOICES')">
        <img  src="/icons/icon-arrow-left.svg"></img>
        <h3>Go Back</h3>
    </go-back>
    <invoice-view-header v-if="invoice" :class="{ hidden: hideInvoiceView}">
        <h4>Status</h4>
        <invoice-status :class="invoice.status" >
            {{invoice.status}}
        </invoice-status>
        <edit-invoice @click="broadcast('EDIT_INVOICE', invoice.id)">
            <h3>Edit</h3>
        </edit-invoice>
        <delete-invoice @click="deleteInvoice(invoice.id);
                                hideInvoicesList = true;
                                broadcast('VIEW_ALL_INVOICES');">
            <h3>Delete</h3>
        </delete-invoice>
        <mark-as-paid v-if="invoice.status === 'pending'" @click="invoice.status = 'paid'">
            <h3>Mark as Paid</h3>
        </mark-as-paid>
        <mark-as-unpaid v-if="invoice.status === 'paid'" @click="invoice.status = 'pending'">
            <h3>Mark as unpaid</h3>
        </mark-as-unpaid>
    </invoice-view-header>
    <invoice-view-body v-if="invoice" :class="{ hidden: hideInvoiceView}">
        <invoice-body-top>
            <invoice-id-description>
                <h3>{{ invoice.id }}</h3>
                <p>{{ invoice.description }}</p>
            </invoice-id-description>
            <invoice-sender-address v-if="invoice.sender_address">
                {{ invoice.sender_address.street }}<br>
                {{ invoice.sender_address.city }}<br>
                {{ invoice.sender_address.postal_code }}<br>
                {{ invoice.sender_address.country }}<br>
            </invoice-sender-address>
        </invoice-body-top>
        <invoice-body-middle>
            <invoice-dates>
                <p>Invoice Date</p>
                <h2>{{ invoice.issue_date }}</h2>
                <p>Payment Due</p>
                <h2>{{ invoice.due_date }}</h2>
            </invoice-dates>
            <invoice-client-name-address v-if="invoice.client_address">
                Bill To
                <h2>{{ invoice.client_name }}</h2>
                {{ invoice.client_address.street }}<br>
                {{ invoice.client_address.city }}<br>
                {{ invoice.client_address.postal_code }}<br>
                {{ invoice.client_address.country }}<br>
            </invoice-client-name-address>
            <invoice-client-email>
                Sent To
                <h3>{{ invoice.client_email }}</h3>
            </invoice-client-email>
        </invoice-body-middle>
        <invoice-body-bottom>
            <line-items-header>
                <heading-item-name>
                    Item Name
                </heading-item-name>
                <heading-item-quantity>
                    QTY.
                </heading-item-quantity>
                <heading-item-price>
                    Price
                </heading-item-price>
                <heading-item-total>
                    Total
                </heading-item-total>
            </line-items-header>
            <line-items v-for="line_item of invoice.line_items">
                <item-name>
                    {{ line_item.name }}
                </item-name>
                <item-quantity>
                    {{ line_item.quantity }}
                </item-quantity>
                <item-price>
                    {{ (line_item.price_unit_cents / 100).toFixed(2) }}
                </item-price>
                <item-total>
                    {{ (line_item.price_total_cents / 100).toFixed(2) }}
                </item-total>
            </line-items>
        </invoice-body-bottom>
        <invoice-total>
            <h3>Amount Due</h3>
            <h2>{{ (invoice.total_cents / 100).toFixed(2) }}</h2>
        </invoice-total>
    </invoice-view-body>
</template>
