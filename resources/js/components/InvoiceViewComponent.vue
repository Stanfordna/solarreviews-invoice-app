<script setup>
    import { fetchInvoice } from '../api.js';
    import { watch, ref } from 'vue';
    import useEventsBus from '../eventBus.js';

    const invoice = ref([]);
    const hideInvoiceView = ref(true);
    const { broadcast, events } = useEventsBus();

    watch(()=>events.value.get('VIEW_INVOICE'), async (id) => {
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


<style src='../../css/invoiceView.css'></style>
<template>
    <go-back :class="{ hidden: hideInvoiceView}" @click="broadcast('VIEW_ALL_INVOICES');
                        hideInvoiceView = true;">
        <img  src="/icons/icon-arrow-left.svg"></img>
        <h3>Go Back</h3>
    </go-back>
    <invoice-view-header :class="{ hidden: hideInvoiceView}">
        <h4>Status</h4>
        <invoice-status :class="invoice.status">{{invoice.status}}</invoice-status>
<!--    
        <heading-count>
            <h1>
                Invoices
            </h1>
            <h4 v-if="invoiceCount > 0">
                There are {{ invoiceCount }} total invoices
            </h4>
            <h4 v-else>
                No invoices
            </h4>
        </heading-count>
        <invoices-filter>
            <status-select :tabindex="tabindex" @blur="closed = true">
                <chosen-status @click="closed = !closed">
                    {{ selectedStatusFilter ? `Filter by ${selectedStatusFilter}` : 'Filter by Status' }}
                </chosen-status>
                <status-options :class="{ hidden: closed }">
                    <invoice-status
                        v-for="(status, i) of statusOptions"
                        :key="i" @click="applyStatusFilter(status)" >
                        {{ status }}
                    </invoice-status>
                </status-options>
            </status-select>
            <img @click="closed = !closed" src="/icons/icon-arrow-down.svg"></img>
        </invoices-filter>
        <new-invoice-button @click="broadcast('NEW_INVOICE');">
            <add-icon>
                <img src="/icons/icon-plus.svg"></img>
            </add-icon>
            <h4>
                New Invoice
            </h4>
        </new-invoice-button>
         -->
    </invoice-view-header>
    <invoice-view-body :class="{ hidden: hideInvoiceView}">
        <!-- <all-invoices v-if="invoices.length !== 0">
            <invoice-summary v-for="(invoice, i) of invoices" :key="i">
                <status-wrapper v-if="selectedStatusFilter == null ||
                                      selectedStatusFilter == 'all' || 
                                      selectedStatusFilter == invoice.status"
                                      @click="broadcast('VIEW_INVOICE', invoice.id);
                                              hideInvoicesList = true;">
                    <invoice-id>{{invoice.id}}</invoice-id>
                    <due-date>{{invoice.due_date}}</due-date>
                    <client-name>{{invoice.client_name}}</client-name>
                    <total-cents>{{(invoice.total_cents / 100).toFixed(2)}}</total-cents>
                    <invoice-status :class="invoice.status">{{invoice.status}}</invoice-status>
                    <img src="/icons/icon-arrow-right.svg"></img>
                </status-wrapper>
            </invoice-summary>
        </all-invoices>
        <no-invoices v-else>
            <img src="/icons/illustration-empty.svg"></img>
            <h2>There is nothing here</h2>
            <p>Create an invoice by clicking the New Invoice button and get started</p>
        </no-invoices> -->
    </invoice-view-body>
</template>
