import { ref } from "vue";
const events = ref(new Map());

export default function useEventsBus(){

    function broadcast(event, ...args) {
        events.value.set(event, args);
        console.log('Event broadcast.\n name:');
        console.log(event);
        console.log('value:');
        console.log(...args);
    }

    return {
        broadcast,
        events
    }
}

