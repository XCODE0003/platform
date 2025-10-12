import { defineStore } from "pinia";
import axiosClient from "../api/axios";
import { useToast } from "@/composables/useToast.js";

export const useTradeStore = defineStore("trade", {
    state: () => ({
        tradingPairs: [],
        loading: false,
        errors: null,
        selectedPair: null,
    }),

    getters: {
        currentUser: (state) => state.user,
        isLoading: (state) => state.loading,
        isAuth: (state) => state.user !== null,
    },

    actions: {
        setTradingPairs(tradingPairs) {
            this.tradingPairs = tradingPairs;
        },
        setSelectedPair(pair) {
            this.selectedPair = pair;
        }
    },
});
