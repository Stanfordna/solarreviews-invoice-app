import { reactive, ref } from "vue";

const events = reactive({
    VIEW_ALL_INVOICES: false,
    VIEW_INVOICE: null,
    NEW_INVOICE: false,
    EDIT_INVOICE: null,
});

export default function useEventsBus() {
    function broadcast(event, payload = null) {
        if (payload === null) {
            console.log(`toggling ${event}`)
            payload = !events[event];
        }
        if (typeof payload === 'string') {
             // in case it's the same invoice id, we make sure the watcher observes
             // a change to null then back tot he same id. Watcher knows to ignore null.
            events[event] = null;
        }
        if (typeof payload === 'object') {
            payload = ref(JSON.parse(JSON.stringify(payload)));
        }
        setTimeout(() => {
            events[event] = payload;
        }, 100)
    }

    return {
        broadcast,
        events
    };
}