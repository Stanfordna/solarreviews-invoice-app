export async function fetchInvoices() {
    try {
      console.log("fetching all invoices...");
      console.log(`/api/invoices`);
      const response = await fetch('/api/invoices');
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}\n${response.message}`);
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
      console.log("fetching one invoice...");
      console.log(`/api/invoices/<${id}>`);
      
      const response = await fetch(`/api/invoices/${id}`);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}\n${response.message}`);
      }
      const json = await response.json();
      // If the API returns a Laravel ResourceCollection, data will be under `data`.
      // Return the array when present, otherwise return the whole payload.
      return (typeof json === 'object' && 'data' in json) ? json.data : json;
    } catch (err) {
        console.error(err);
    }}
