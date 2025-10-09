export async function fetchInvoices() {
    try {
        console.log('/api/invoices');
        const response = await fetch('/api/invoices');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}\nmessage: ${response.message}`);
        }
        const json = await response.json();
        // If the API returns a Laravel ResourceCollection, data will be under `data`.
        // Return the array when present, otherwise return the whole payload.
        return Array.isArray(json?.data) ? json.data : json;
    } catch (err) {
        console.error(err);
    }
}

export async function fetchInvoice(id) {
    try {
        console.log(`/api/invoices/<${id}>`);
        
        const response = await fetch(`/api/invoices/${id}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}\nmessage: ${response.message}`);
        }
        const json = await response.json();
        // If the API returns a Laravel ResourceCollection, data will be under `data`.
        // Return the array when present, otherwise return the whole payload.
        return (typeof json === 'object' && 'data' in json) ? json.data : json;
    } catch (err) {
        console.error(err);
    }
}

export async function addInvoice(invoiceData) {
    // TBD TODO: get rid of unnecessary fields like total?
    try {
        console.log("creating new invoice...");
        console.log('/api/invoices');
        const response = await fetch('/api/invoices', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(invoiceData),
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}\nmessage: ${response.message}`);
        }
        const json = await response.json();
        // Todo: Make a success popup?
        return (typeof json === 'object' && 'message' in json) ? json.message : json;
    } catch (err) {
        console.error('Error creating invoice:', err);
    }
};

export async function editInvoice(id, invoiceData) {
    // TBD TODO: get rid of unnecessary fields like total?
    try {
        console.log("editing invoice...");
        console.log(`/api/invoices/<${id}>`);
        const response = await fetch(`/api/invoices/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(invoiceData),
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}\nmessage: ${response.message}`);
        }
        const json = await response.json();
        // Todo: Make a success popup?
        return (typeof json === 'object' && 'message' in json) ? json.message : json;
    } catch (err) {
        console.error('Error updating invoice:', err);
    }
};

export async function deleteInvoice(id) {
    try {
        console.log("deleting invoice...");
        console.log(`/api/invoices/<${id}>`);
        const response = await fetch(`/api/invoices/${id}`, {
            method: 'DELETE',
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}\nmessage: ${response.message}`);
        }
        const json = await response.json();
        // Todo: Make a success popup?
        return (typeof json === 'object' && 'message' in json) ? json.message : json;
    } catch (err) {
        console.error('Error deleting invoice:', err);
    }
};
