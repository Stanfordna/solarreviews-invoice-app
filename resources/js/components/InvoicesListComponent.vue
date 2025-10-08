<script setup>
    import { fetchInvoices } from '../api.js';
    import { watch, ref, onMounted } from 'vue';
    import useEventsBus from '../eventBus.js';

    const invoiceCount = ref(0);
    const tabindex = ref(0);
    const selectedStatusFilter = ref(null);
    const invoices = ref([]);
    const closed = ref(true);
    const statusOptions = ref(['all', 'draft', 'pending', 'paid']);
    const hideInvoicesList = ref(false);
    const { broadcast, events } = useEventsBus();
    watch(()=>events.value.get('VIEW_ALL_INVOICES'), async (val) => {
        try {
            invoices.value = await fetchInvoices();
            invoiceCount.value = invoices.value.length;
        } catch (err) {
            console.error('Failed to load invoices on mount', err);
        }
        hideInvoicesList.value = false;
    })


    const applyStatusFilter = async (status) => {
        selectedStatusFilter.value = status;
        closed.value = true;
        try {
            invoices.value = await fetchInvoices();
            invoiceCount.value = invoices.value.length;
        } catch (err) {
            console.error('Failed to fetch invoices for filter', err);
        }
    };

    onMounted(async () => {
        try {
            invoices.value = await fetchInvoices();
            invoiceCount.value = invoices.value.length;
        } catch (err) {
            console.error('Failed to load invoices on mount', err);
        }
    })
</script>

<style src='../../css/invoicesList.css'></style>
<template>
    <invoices-list-header :class="{ hidden: hideInvoicesList}">
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
    </invoices-list-header>
    <invoices-list-body :class="{ hidden: hideInvoicesList}">
        <all-invoices v-if="invoices.length !== 0">
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
        </no-invoices>
    </invoices-list-body>
</template>
