import { defineStore } from 'pinia';

export const useModalStore = defineStore('modal', {
    state: () => ({
        current: null,
    }),
    actions: {
        open(key) {
            this.current = key;
        },
        close(key) {
            if (this.current === key) this.current = null;
        },
        toggle(key) {
            if (this.current === key) this.current = null;
            else this.current = key;
        },
    },
    getters: {
        isOpen: (state) => (key) => state.current === key,
    },
});
