import { defineStore } from 'pinia';
import axiosClient from '../api/axios';
import { useToast } from '@/composables/useToast.js';

export const useTradeStore = defineStore('trade', {
    state: () => ({
        tradingPairs: [],
        price: null,
        volumeChange: null,
        volume: null,
        high: null,
        low: null,
        bills: [],
        selectedBillId: null,
        loading: false,
        errors: null,
        selectedPair: null,
        orders: [],
        positions: [],
        lastClosedPosition: null,
        hiddenOpenPairId: null,
    }),

    getters: {
        currentUser: (state) => state.user,
        isLoading: (state) => state.loading,
        isAuth: (state) => state.user !== null,

        selectedBill: (state) => {
            return state.bills.find((b) => b.id === state.selectedBillId) ?? null;
        },
    },

    actions: {
        async fetchOrders() {
            try {
                const { data } = await axiosClient.get('/api/trade/orders');
                this.orders = data.orders || [];
                this.positions = data.positions || [];
                // If there is no open position for the hidden pair anymore, clear the hidden flag
                if (this.hiddenOpenPairId) {
                    const stillOpenForHiddenPair = this.positions.some(
                        (p) => p.pair_id === this.hiddenOpenPairId && p.status === 'open'
                    );
                    if (!stillOpenForHiddenPair) {
                        this.hiddenOpenPairId = null;
                    }
                }
            } catch (e) {}
        },
        async placeOrder(payload) {
            const { data } = await axiosClient.post('/api/trade/orders', payload);
            if (data?.bills) {
                this.setBills(data.bills);
            }
            await this.fetchOrders();
            return data?.order ?? data;
        },
        async cancelOrder(id) {
            // Try to capture order/position context before cancellation
            const order = this.orders.find((o) => o.id === id) || null;
            const pairId = order?.pair_id ?? this.selectedPair?.id ?? null;
            const openPos = pairId
                ? this.positions.find((p) => p.pair_id === pairId && p.status === 'open')
                : null;

            const { data } = await axiosClient.post(`/api/trade/orders/${id}/cancel`);

            // Show temporary exit arrow (5s) at current price
            const nowIso = new Date().toISOString();
            const closePrice = this.price ?? openPos?.entry_price ?? null;
            if (pairId && closePrice !== null) {
                this.lastClosedPosition = {
                    pair_id: pairId,
                    entry_price: openPos?.entry_price ?? null,
                    close_price: Number(closePrice),
                    side: openPos?.side ?? order?.side ?? 'buy',
                    quantity: openPos?.quantity ?? order?.amount ?? null,
                    updated_at: nowIso,
                    closed_at: nowIso,
                };
                setTimeout(() => {
                    this.lastClosedPosition = null;
                }, 5000);

                // Hide open position line after 10 seconds for this pair
                setTimeout(() => {
                    this.hiddenOpenPairId = pairId;
                }, 10000);
            }

            await this.fetchOrders();
            if (data?.bills) {
                this.setBills(data.bills);
            }
        },
        async fillOrder(id, price) {
            await axiosClient.post(`/api/trade/orders/${id}/fill`, { price });
            await this.fetchOrders();
        },
        async closePosition(id, price) {
            const { data } = await axiosClient.post(`/api/trade/positions/${id}/close`, { price });
            this.lastClosedPosition = data.position ?? data;
            if (data?.bills) {
                this.setBills(data.bills);
            }
            setTimeout(() => {
                this.lastClosedPosition = null;
            }, 5000);
            await this.fetchOrders();
        },
        setBills(bills) {
            this.bills = bills;
            if ((!this.selectedBillId || !this.bills.some(b => b.id === this.selectedBillId)) && bills.length) {
                this.selectedBillId = bills[0].id;
            }
        },
        setSelectedBill(id) {
            this.selectedBillId = id;
        },
        setTradingPairs(tradingPairs) {
            this.tradingPairs = tradingPairs;
        },
        setSelectedPair(pair) {
            this.selectedPair = pair;
        },
        setPrice(price) {
            this.price = price;
        },
        setVolume(volume) {
            this.volume = volume;
        },
        setHigh(high) {
            this.high = high;
        },
        setLow(low) {
            this.low = low;
        },
        setVolumeChange(volumeChange) {
            this.volumeChange = volumeChange;
        },
    },
});