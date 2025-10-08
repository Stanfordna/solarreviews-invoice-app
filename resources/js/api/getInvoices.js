export async function fetchInvoices() {
    try { // using fetch API rather than axios since this is a simple app
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
