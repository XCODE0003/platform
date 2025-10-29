// Unified datafeed: history from Laravel /api/market/bars, realtime via quotes socket (Socket.IO)

import axiosClient from '@/api/axios';
import { useTradeStore } from '@/stores/tradeStore.js';

const DEBUG_QUOTES =
    (typeof window !== 'undefined' && window.DEBUG_QUOTES === true) ||
    (typeof import.meta !== 'undefined' &&
        String(import.meta.env?.VITE_DEBUG_QUOTES || '') === 'true');
const dbg = (...args) => {
    if (DEBUG_QUOTES) console.log('[quotes]', ...args);
};

const configurationData = {
    supported_resolutions: [
        '1',
        // '3',
        // '5',
        // '15',
        // '30',
        // '60',
        // '120',
        // '240',
        // '1D',
    ],
};

export const onReady = (cb) => {
    dbg('onReady -> config', configurationData);
    setTimeout(() => cb(configurationData));
};

function parsePairSymbol(symbolName) {
    // Expect format: PAIR:123
    if (!symbolName) return null;
    const parts = String(symbolName).split(':');
    if (parts[0] !== 'PAIR') return null;
    const id = Number(parts[1]);
    if (!Number.isFinite(id)) return null;
    return id;
}

export const resolveSymbol = (
    symbolName,
    onSymbolResolvedCallback,
    onResolveErrorCallback,
) => {
    const pairId = parsePairSymbol(symbolName);
    if (!pairId) {
        dbg('resolveSymbol error: invalid symbol', symbolName);
        onResolveErrorCallback?.();
        return;
    }
    fetch(
        new URL('/api/market/pair?pair_id=' + pairId, window.location.origin),
        { credentials: 'same-origin' },
    )
        .then((r) => (r.ok ? r.json() : Promise.reject(r.status)))
        .then((info) => {
            const display = info?.display || `PAIR:${pairId}`;
            const symbolInfo = {
                ticker: `PAIR:${pairId}`,
                name: display,
                session: '24x7',
                timezone: 'Etc/UTC',
                minmov: 1,
                pricescale: 100000,
                has_intraday: true,
                intraday_multipliers: configurationData.supported_resolutions,
                supported_resolutions: configurationData.supported_resolutions,
                volume_precision: 1,
                data_status: 'streaming',
            };
            dbg('resolveSymbol ok', { symbolName, pairId, symbolInfo });
            onSymbolResolvedCallback(symbolInfo);
        })
        .catch(() => {
            const fallback = {
                ticker: `PAIR:${pairId}`,
                name: `PAIR:${pairId}`,
                session: '24x7',
                timezone: 'Etc/UTC',
                minmov: 1,
                pricescale: 100000,
                has_intraday: true,
                intraday_multipliers: configurationData.supported_resolutions,
                supported_resolutions: configurationData.supported_resolutions,
                volume_precision: 1,
                data_status: 'streaming',
            };
            dbg('resolveSymbol fallback', { symbolName, pairId, fallback });
            onSymbolResolvedCallback(fallback);
        });
};

export const getBars = async (
    symbolInfo,
    resolution,
    periodParams,
    onHistoryCallback,
    onErrorCallback,
) => {
    try {
        const pairId = parsePairSymbol(symbolInfo.ticker);
        if (!pairId) throw new Error('invalid_pair_symbol');
        dbg('getBars request', {
            pairId,
            resolution,
            from: periodParams.from,
            to: periodParams.to,
        });
        const url = new URL('/api/market/bars', window.location.origin);
        url.searchParams.set('pair_id', String(pairId));
        url.searchParams.set('resolution', String(resolution));
        url.searchParams.set('from', String(periodParams.from));
        url.searchParams.set('to', String(periodParams.to));
        url.searchParams.set('source', 'twelvedata');

        const resp = await fetch(url.toString(), {
            credentials: 'same-origin',
        });
        if (!resp.ok) throw new Error(`market ${resp.status}`);
        const data = await resp.json();
        const bars = Array.isArray(data?.bars) ? data.bars : [];
        if (bars.length > 0) {
            const tradeStore = useTradeStore();
            const lastBar = bars[bars.length - 1];
            tradeStore.setPrice(lastBar.close);
            tradeStore.setVolume(lastBar.volume);
            tradeStore.setHigh(lastBar.high);
            tradeStore.setLow(lastBar.low);
            tradeStore.setVolumeChange(
                lastBar.volume - (tradeStore.volume || 0),
            );
        }
        dbg('getBars response', { pairId, resolution, count: bars.length });
        onHistoryCallback(bars, { noData: bars.length === 0 });
    } catch (e) {
        dbg('getBars error', e);
        onErrorCallback?.(e);
    }
};

const subs = new Map();

export const subscribeBars = (
    symbolInfo,
    resolution,
    onRealtimeCallback,
    subscriberUID,
    onResetCacheNeededCallback,
) => {
    const pairId = parsePairSymbol(symbolInfo.ticker);
    if (!pairId) return;
    dbg('subscribeBars', { pairId, resolution, subscriberUID });
    if (!window.Echo || !window.Echo.channel) {
        console.warn('[quotes] Echo not initialized');
        return;
    }
    // Touch relay TTL so backend keeps WS open for this pair/resolution
    try {
        axiosClient
            .post('/api/quotes/ensure', {
                pair_id: pairId,
                resolution: String(resolution),
                ttl: 600,
            })
            .catch(() => {});
    } catch (_) {}
    // pick channel by resolution: pair.{id}.{res}
    const chanName = 'pair.' + pairId + '.' + String(resolution);
    const channel = window.Echo.channel(chanName);
    dbg('listen channel', chanName);
    const tradeStore = useTradeStore();
    const handler = (bar) => {
        dbg('bar parsed', bar);
        const nextPrice = Number(bar.close);
        const nextVol = Number(bar.volume);
        const nextHigh = Number(bar.high);
        const nextLow = Number(bar.low);
        const prevVol = Number(tradeStore.volume) || 0;
        tradeStore.setPrice(nextPrice);
        tradeStore.setHigh(nextHigh);
        tradeStore.setLow(nextLow);
        tradeStore.setVolumeChange(nextVol - prevVol);
        tradeStore.setVolume(nextVol);
        const tvBar = {
            time: Number(bar.time),
            open: Number(bar.open),
            high: Number(bar.high),
            low: Number(bar.low),
            close: Number(bar.close),
            volume: Number(bar.volume),
            closed: Boolean(bar.closed),
        };
        console.log('[quotes] BAR TV', tvBar);
        dbg('tv onRealtimeCallback', tvBar);
        onRealtimeCallback(tvBar);
    };
    channel.listen('.bar', (payload) => {
        console.log('[quotes] BAR RAW', payload);
        dbg('bar raw', payload);
        try {
            let data = payload;
            if (payload && typeof payload === 'string') {
                data = JSON.parse(payload);
            } else if (payload && typeof payload.data === 'string') {
                data = JSON.parse(payload.data);
            }
            handler(data);
        } catch (e) {
            console.warn('[quotes] parse error', e);
        }
    });
    subs.set(subscriberUID, {
        channel,
        handler,
        pairId,
        resolution: String(resolution),
    });
};

export const unsubscribeBars = (subscriberUID) => {
    const s = subs.get(subscriberUID);
    if (!s) return;
    dbg('unsubscribeBars', { pairId: s?.pairId, subscriberUID });
    try {
        s.channel.stopListening('.bar');
    } catch {}
    try {
        window.Echo.leave('pair.' + s.pairId + '.' + s.resolution);
    } catch {}
    subs.delete(subscriberUID);
};

const UnifiedDatafeed = {
    onReady,
    resolveSymbol,
    getBars,
    subscribeBars,
    unsubscribeBars,
};
export default UnifiedDatafeed;
