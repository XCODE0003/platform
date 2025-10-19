// Binance-backed datafeed for TradingView widget
// History via REST klines; realtime via WebSocket aggTrade to approximate updates

// Use backend proxy to avoid CORS and support local dev
const BINANCE_REST_PROXY = "/api/binance";
const BINANCE_WS = "wss://stream.binance.com:9443/ws";

const configurationData = {
  supported_resolutions: ["1", "3", "5", "15", "30", "60", "120", "240", "1D"],
};

const tvToBinance = {
  "1": "1m",
  "3": "3m",
  "5": "5m",
  "15": "15m",
  "30": "30m",
  "60": "1h",
  "120": "2h",
  "240": "4h",
  "1D": "1d",
};

function resolutionToMs(resolution) {
  if (resolution.endsWith("D")) return 24 * 60 * 60 * 1000;
  const n = parseInt(resolution, 10);
  if (!Number.isNaN(n)) return n * 60 * 1000;
  return 5 * 60 * 1000;
}

export const onReady = (cb) => {
  setTimeout(() => cb(configurationData));
};

export const resolveSymbol = (
  symbolName,
  onSymbolResolvedCallback,
  onResolveErrorCallback
) => {
  // Normalize from pair like BTC/ETH or BTCETH
  let s = symbolName || "BTCUSDT";
  if (s.includes('/')) {
    s = s.replace('/', '')
  }
  // If user passes inverted like BTCETH which doesn't exist on spot, try flip
  const tryFlip = s.length === 6 ? (s.slice(3) + s.slice(0,3)) : s;
  const symbolInfo = {
    ticker: tryFlip,
    name: tryFlip,
    session: "24x7",
    timezone: "Etc/UTC",
    minmov: 1,
    pricescale: 100,
    has_intraday: true,
    intraday_multipliers: configurationData.supported_resolutions,
    supported_resolutions: configurationData.supported_resolutions,
    volume_precision: 1,
    data_status: "streaming",
  };
  setTimeout(() => onSymbolResolvedCallback(symbolInfo));
};

export const getBars = async (
  symbolInfo,
  resolution,
  periodParams,
  onHistoryCallback,
  onErrorCallback
) => {
  try {
    const binanceInterval = tvToBinance[resolution] || "1m";
    // Binance accepts ms timestamps
    const startTime = periodParams.from * 1000;
    const endTime = periodParams.to * 1000;

    const url = new URL(window.location.origin + BINANCE_REST_PROXY + "/klines");
    url.searchParams.set("symbol", symbolInfo.ticker);
    url.searchParams.set("interval", binanceInterval);
    url.searchParams.set("startTime", String(startTime));
    url.searchParams.set("endTime", String(endTime));
    url.searchParams.set("limit", "1000");

    const resp = await fetch(url.toString());
    if (!resp.ok) {
      throw new Error(`Binance error ${resp.status}`);
    }
    const data = await resp.json();
    // Kline schema: [ open time, open, high, low, close, volume, close time, ... ]
    const bars = data.map((k) => ({
      time: k[0],
      open: Number(k[1]),
      high: Number(k[2]),
      low: Number(k[3]),
      close: Number(k[4]),
      volume: Number(k[5]),
    }));

    onHistoryCallback(bars, { noData: bars.length === 0 });
  } catch (e) {
    onErrorCallback(e);
  }
};

const wsBySub = new Map();

export const subscribeBars = (
  symbolInfo,
  resolution,
  onRealtimeCallback,
  subscriberUID,
  onResetCacheNeededCallback
) => {
  // Use kline stream for the selected interval for precise candle updates
  const interval = tvToBinance[resolution] || "1m";
  const stream = `${symbolInfo.ticker.toLowerCase()}@kline_${interval}`;
  const ws = new WebSocket(`${BINANCE_WS}/${stream}`);

  ws.onmessage = (evt) => {
    const msg = JSON.parse(evt.data);
    const k = msg.k; // kline payload
    const bar = {
      time: k.t, // open time in ms
      open: Number(k.o),
      high: Number(k.h),
      low: Number(k.l),
      close: Number(k.c),
      volume: Number(k.v),
    };
    onRealtimeCallback(bar);
  };

  ws.onerror = () => {
    // noop
  };

  wsBySub.set(subscriberUID, ws);
};

export const unsubscribeBars = (subscriberUID) => {
  const ws = wsBySub.get(subscriberUID);
  if (ws) {
    try { ws.close(); } catch (_) {}
  }
  wsBySub.delete(subscriberUID);
};

const BinanceDatafeed = { onReady, resolveSymbol, getBars, subscribeBars, unsubscribeBars };
export default BinanceDatafeed;


