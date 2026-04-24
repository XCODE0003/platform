import { defineStore } from 'pinia';
import axiosClient from '../api/axios';
import { useToast } from '@/composables/useToast.js';

export const useTradeStore = defineStore('trade', {
    state: () => ({
        tradingPairs: [],
        price: null,
        prices: {}, // pair_id -> latest price
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
        selectedHistoricalTrade: null,
        selectedOpenPositionId: null,  // open position shown on chart
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
            const { data } = await axiosClient.post(`/api/trade/orders/${id}/cancel`);
            await this.fetchOrders();
            if (data?.bills) {
                this.setBills(data.bills);
            }
        },
        async updateTpSl(id, takeProfit, stopLoss) {
            await axiosClient.patch(`/api/trade/positions/${id}/tpsl`, {
                take_profit: takeProfit || null,
                stop_loss:   stopLoss   || null,
            });
            await this.fetchOrders();
        },
        async closePosition(id, _ignoredPrice = null, quantity = null) {
            // Close price is resolved server-side; frontend cannot choose it.
            const payload = {};
            if (quantity !== null) payload.quantity = quantity;
            const { data } = await axiosClient.post(`/api/trade/positions/${id}/close`, payload);
            if (data?.bills) {
                this.setBills(data.bills);
            }
            // Show exit arrow on chart for 5s (only for full close)
            if (data?.status === 'closed' || data?.status === 'partial') {
                this.lastClosedPosition = data;
                setTimeout(() => { this.lastClosedPosition = null; }, 5000);
            }
            // Hide open position line only on full close
            if (data?.status === 'closed' && data?.pair_id) {
                this.hiddenOpenPairId = data.pair_id;
                setTimeout(() => { this.hiddenOpenPairId = null; }, 10000);
            }
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
            if (price !== null && this.selectedPair?.id) {
                this.prices = { ...this.prices, [this.selectedPair.id]: price };
            }
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
        setSelectedHistoricalTrade(trade) {
            if (trade && this.selectedHistoricalTrade?.id === trade.id) {
                this.selectedHistoricalTrade = null;
            } else {
                this.selectedHistoricalTrade = trade;
                this.selectedOpenPositionId = null; // exit open-position mode
            }
        },
        setSelectedOpenPosition(id) {
            this.selectedOpenPositionId = id;
            this.selectedHistoricalTrade = null; // exit historical mode
        },
    },
});