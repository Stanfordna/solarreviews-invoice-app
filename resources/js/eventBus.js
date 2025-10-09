import { reactive } from "vue";

const events = reactive({
    VIEW_ALL_INVOICES: false,
    VIEW_INVOICE: null,
    NEW_INVOICE: false,
    EDIT_INVOICE: null,
});

export default function useEventsBus() {
    function broadcast(event, payload = null) {
        // Clear all events (optional, if you want only one active at a time)
        if (payload === null) {
            console.log(`toggling ${event}`)
            events[event] = !events[event];
        }
        else {
            events[event] = payload;
        }
    }

    return {
        broadcast,
        events
    };
}