import { defineStore } from 'pinia';

export const useModalStore = defineStore('modal', {
    state: () => ({
        current: null,
        payload: null,
    }),
    actions: {
        open(key, payload = null) {
            this.current = key;
            this.payload = payload;
        },
        close(key) {
            if (this.current === key) {
                this.current = null;
                this.payload = null;
            }
        },
        toggle(key) {
            if (this.current === key) {
                this.current = null;
                this.payload = null;
            } else {
                this.current = key;
                this.payload = null;
            }
        },
    },
    getters: {
        isOpen: (state) => (key) => state.current === key,
        getPayload: (state) => state.payload,
    },
});
