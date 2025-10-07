import { defineStore } from 'pinia';

export const useModalStore = defineStore('modal', {
    state: () => ({
        openMap: new Set(),
    }),
    actions: {
        open(key) {
            this.openMap.add(key);
        },
        close(key) {
            this.openMap.delete(key);
        },
        toggle(key) {
            if (this.isOpen(key)) this.close(key);
            else this.open(key);
        },
    },
    getters: {
        isOpen: (state) => (key) => state.openMap.has(key),
    },
});
