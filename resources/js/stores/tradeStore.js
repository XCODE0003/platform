import { defineStore } from 'pinia';
import axiosClient from '../api/axios';
import { useToast } from '@/composables/useToast.js';

// Live price feeds for pairs with open positions, kept outside Pinia state
// (Echo channels and intervals are non-serialisable). Map: pairId -> handle.
const pairFeeds = new Map();

const FEED_RESOLUTION = '1';
const FEED_TTL_SECONDS = 600;
const FEED_REFRESH_MS = 5 * 60 * 1000;

function ensureRelay(pairId) {
    return axiosClient
        .post('/api/quotes/ensure', {
            pair_id: pairId,
            resolution: FEED_RESOLUTION,
            ttl: FEED_TTL_SECONDS,
        })
        .catch(() => {});
}

function startFeed(store, pairId) {
    const id = Number(pairId);
    if (!Number.isFinite(id)) return;
    if (pairFeeds.has(id)) return;
    if (typeof window === 'undefined' || !window.Echo?.channel) return;

    ensureRelay(id);

    const chanName = `pair.${id}.${FEED_RESOLUTION}`;
    const channel = window.Echo.channel(chanName);

    const onBar = (payload) => {
        try {
            let data = payload;
            if (payload && typeof payload === 'string') {
                data = JSON.parse(payload);
            } else if (payload && typeof payload.data === 'string') {
                data = JSON.parse(payload.data);
            }
            const close = Number(data?.close);
            if (!Number.isFinite(close)) return;
            const isSelected =
                store.selectedPair?.id != null &&
                Number(store.selectedPair.id) === id;
            if (isSelected) {
                // Header price tracks live ticks immediately, even before
                // the chart's own subscription is ready (or when the chart
                // is on a different resolution channel).
                store.setPrice(close);
                const high = Number(data?.high);
                const low  = Number(data?.low);
                const vol  = Number(data?.volume);
                if (Number.isFinite(high)) store.setHigh(high);
                if (Number.isFinite(low))  store.setLow(low);
                if (Number.isFinite(vol)) {
                    const prevVol = Number(store.volume) || 0;
                    store.setVolumeChange(vol - prevVol);
                    store.setVolume(vol);
                }
            } else {
                store.setPairPrice(id, close);
            }
        } catch (_) {}
    };

    channel.listen('.bar', onBar);
    const refresh = setInterval(() => ensureRelay(id), FEED_REFRESH_MS);

    pairFeeds.set(id, { channel, chanName, refresh, onBar });
}

function stopFeed(pairId) {
    const id = Number(pairId);
    const handle = pairFeeds.get(id);
    if (!handle) return;
    try { clearInterval(handle.refresh); } catch (_) {}
    // Pass the specific callback — stopListening without it removes ALL '.bar'
    // listeners on the channel, including the chart's subscribeBars handler
    // when it shares the same pair.{id}.1 channel.
    try { handle.channel.stopListening('.bar', handle.onBar); } catch (_) {}
    pairFeeds.delete(id);
}

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
                // Keep live price feeds aligned with the set of pairs that
                // currently have open positions, so PnL updates regardless of
                // which pair the user is viewing on the chart.
                this.syncPairPriceFeeds();
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
            // Re-align live feeds: spin one up for the new pair, drop feeds
            // for pairs that are no longer selected and have no open position.
            this.syncPairPriceFeeds();
        },
        setPrice(price) {
            this.price = price;
            if (price !== null && this.selectedPair?.id) {
                this.setPairPrice(this.selectedPair.id, price);
            }
        },
        setPairPrice(pairId, price) {
            const id = Number(pairId);
            const value = Number(price);
            if (!Number.isFinite(id) || !Number.isFinite(value)) return;
            this.prices = { ...this.prices, [id]: value };
        },
        syncPairPriceFeeds() {
            const targetIds = new Set();
            for (const p of this.positions) {
                if (p?.status === 'open' && p.pair_id != null) {
                    targetIds.add(Number(p.pair_id));
                }
            }
            // Always keep a feed for the currently selected pair so the
            // header price updates live regardless of whether there's an
            // open position on it or whether the chart has subscribed yet.
            if (this.selectedPair?.id != null) {
                targetIds.add(Number(this.selectedPair.id));
            }
            for (const id of targetIds) {
                if (!pairFeeds.has(id)) startFeed(this, id);
            }
            for (const id of [...pairFeeds.keys()]) {
                if (!targetIds.has(id)) stopFeed(id);
            }
        },
        stopAllPairPriceFeeds() {
            for (const id of [...pairFeeds.keys()]) stopFeed(id);
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