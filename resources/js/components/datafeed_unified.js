// Unified datafeed: history from Laravel /api/market/bars, realtime via quotes socket (Socket.IO)

import { io } from 'socket.io-client'

const QUOTES_SOCKET_URL = `${window.location.protocol}//${window.location.hostname}:${Number(import.meta.env.VITE_QUOTES_SOCKET_PORT || 3100)}`

const configurationData = {
  supported_resolutions: ["1", "3", "5", "15", "30", "60", "120", "240", "1D"],
}

export const onReady = (cb) => {
  setTimeout(() => cb(configurationData))
}

function parsePairSymbol(symbolName) {
  // Expect format: PAIR:123
  if (!symbolName) return null
  const parts = String(symbolName).split(':')
  if (parts[0] !== 'PAIR') return null
  const id = Number(parts[1])
  if (!Number.isFinite(id)) return null
  return id
}

export const resolveSymbol = (symbolName, onSymbolResolvedCallback, onResolveErrorCallback) => {
  const pairId = parsePairSymbol(symbolName)
  if (!pairId) {
    onResolveErrorCallback?.()
    return
  }
  fetch(new URL('/api/market/pair?pair_id='+pairId, window.location.origin), { credentials: 'same-origin' })
    .then(r => r.ok ? r.json() : Promise.reject(r.status))
    .then(info => {
      const display = info?.display || `PAIR:${pairId}`
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
      }
      onSymbolResolvedCallback(symbolInfo)
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
      }
      onSymbolResolvedCallback(fallback)
    })
}

export const getBars = async (symbolInfo, resolution, periodParams, onHistoryCallback, onErrorCallback) => {
  try {
    const pairId = parsePairSymbol(symbolInfo.ticker)
    if (!pairId) throw new Error('invalid_pair_symbol')
    const url = new URL('/api/market/bars', window.location.origin)
    url.searchParams.set('pair_id', String(pairId))
    url.searchParams.set('resolution', String(resolution))
    url.searchParams.set('from', String(periodParams.from))
    url.searchParams.set('to', String(periodParams.to))

    const resp = await fetch(url.toString(), { credentials: 'same-origin' })
    if (!resp.ok) throw new Error(`market ${resp.status}`)
    const data = await resp.json()
    const bars = Array.isArray(data?.bars) ? data.bars : []
    onHistoryCallback(bars, { noData: bars.length === 0 })
  } catch (e) {
    onErrorCallback?.(e)
  }
}

const subs = new Map()

export const subscribeBars = (symbolInfo, resolution, onRealtimeCallback, subscriberUID, onResetCacheNeededCallback) => {
  const pairId = parsePairSymbol(symbolInfo.ticker)
  if (!pairId) return
  let socket = subs.get(subscriberUID)?.socket
  if (!socket) {
    socket = io(QUOTES_SOCKET_URL, { transports: ['websocket'] })
  }
  const handler = (bar) => {
    onRealtimeCallback(bar)
  }
  socket.on('bar', handler)
  socket.emit('subscribe', { pairId, resolution: String(resolution) })
  subs.set(subscriberUID, { socket, handler, pairId, resolution: String(resolution) })
}

export const unsubscribeBars = (subscriberUID) => {
  const s = subs.get(subscriberUID)
  if (!s) return
  try {
    s.socket.off('bar', s.handler)
    s.socket.emit('unsubscribe', { pairId: s.pairId, resolution: s.resolution })
    // Keep socket open for reuse by other subs
  } catch {}
  subs.delete(subscriberUID)
}

const UnifiedDatafeed = { onReady, resolveSymbol, getBars, subscribeBars, unsubscribeBars }
export default UnifiedDatafeed


