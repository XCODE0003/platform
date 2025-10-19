import 'dotenv/config'
import { createServer } from 'http'
import { Server } from 'socket.io'
import mysql from 'mysql2/promise'
import WebSocket from 'ws'

const httpServer = createServer()
const io = new Server(httpServer, {
  cors: { origin: '*', methods: ['GET','POST'] },
})

const db = await mysql.createPool({
  host: process.env.DB_HOST || '127.0.0.1',
  user: process.env.DB_USERNAME || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_DATABASE || 'platform',
  port: Number(process.env.DB_PORT || 3306),
  waitForConnections: true,
  connectionLimit: 5,
})

function tvToBinance(res) {
  const map = { '1':'1m','3':'3m','5':'5m','15':'15m','30':'30m','60':'1h','120':'2h','240':'4h','1D':'1d' }
  return map[res] || '1m'
}

function binanceKlineStream(symbol, interval) {
  const wsUrl = `wss://stream.binance.com:9443/ws/${symbol.toLowerCase()}@kline_${interval}`
  return new WebSocket(wsUrl)
}

function twelveDataStream(symbol, interval) {
  // TwelveData realtime via websocket (paid), placeholder for demo
  // For now return null to indicate not implemented
  return null
}

function finnhubStream(symbol) {
  // Finnhub realtime via ws: wss://ws.finnhub.io?token=...
  // Placeholder until API key is configured
  return null
}

async function resolvePairSource(pairId) {
  const [rows] = await db.query(
    `select p.id as pair_id, p.asset_class, p.default_source,
            ps.provider, ps.provider_symbol
     from pairs p
     left join pair_sources ps on ps.pair_id = p.id
     where p.id = ?
     order by (ps.provider = p.default_source) desc, ps.priority asc
     limit 1`,
    [pairId]
  )
  return rows[0] || null
}

const wsByRoom = new Map()
const timerByRoom = new Map()

async function getProviderConfig(code) {
  const [rows] = await db.query(
    'select code, base_url from data_providers where code = ? and enabled = 1 limit 1',
    [code]
  )
  return rows[0] || null
}

function splitFxSymbol(sym) {
  // Expect 6-letter like EURUSD; fallback to with slash
  if (!sym) return null
  if (sym.includes('/')) {
    const [from, to] = sym.split('/')
    return { from: from.toUpperCase(), to: to.toUpperCase() }
  }
  if (sym.length === 6) {
    return { from: sym.slice(0,3).toUpperCase(), to: sym.slice(3).toUpperCase() }
  }
  return null
}

function resolutionMs(res) {
  if (res.endsWith && res.endsWith('D')) return 24*60*60*1000
  const n = parseInt(res, 10)
  if (!Number.isNaN(n)) return n*60*1000
  return 60*1000
}

function startFxPolling(roomKey, baseUrl, from, to, res) {
  const period = Math.max(60*1000, resolutionMs(res))
  const accessKey = process.env.EXCHANGERATE_ACCESS_KEY || ''
  const url = new URL('/convert', baseUrl || 'https://api.exchangerate.host')
  url.searchParams.set('from', from)
  url.searchParams.set('to', to)
  url.searchParams.set('amount', '1')
  if (accessKey) url.searchParams.set('access_key', accessKey)

  let lastBarTs = 0
  const timer = setInterval(async () => {
    try {
      const resp = await fetch(url.toString())
      if (!resp.ok) return
      const data = await resp.json()
      // Support both exchangerate.host and apilayer-like responses
      let rate = data?.result
      if (rate == null && data?.quotes) {
        const key = (from + to).toUpperCase()
        rate = data.quotes[key]
      }
      if (rate == null) return
      const now = Date.now()
      const step = resolutionMs(res)
      const barTime = now - (now % step)
      // Emit flat OHLC bar at this tick (pseudo real-time)
      const price = Number(rate)
      const bar = { time: barTime, open: price, high: price, low: price, close: price, volume: 0, closed: lastBarTs !== 0 && barTime !== lastBarTs }
      lastBarTs = barTime
      io.to(roomKey).emit('bar', bar)
    } catch {}
  }, period)
  timerByRoom.set(roomKey, timer)
}

io.on('connection', (socket) => {
  socket.on('subscribe', async ({ pairId, resolution = '1' }) => {
    try {
      const key = `${pairId}:${resolution}`
      if (wsByRoom.has(key)) {
        socket.join(key)
        return
      }

      const src = await resolvePairSource(pairId)
      if (!src) {
        socket.emit('error', { message: 'pair_not_found' })
        return
      }

      socket.join(key)

      if (src.provider === 'binance') {
        const interval = tvToBinance(resolution)
        const ws = binanceKlineStream(src.provider_symbol, interval)
        ws.onmessage = (evt) => {
          try {
            const msg = JSON.parse(evt.data)
            const k = msg.k
            if (!k) return
            const bar = {
              time: k.t,
              open: Number(k.o),
              high: Number(k.h),
              low: Number(k.l),
              close: Number(k.c),
              volume: Number(k.v),
              closed: k.x,
            }
            io.to(key).emit('bar', bar)
          } catch {}
        }
        ws.onerror = () => {}
        ws.onclose = () => { wsByRoom.delete(key) }
        wsByRoom.set(key, ws)
      } else if (src.provider === 'exchangeratehost') {
        const prov = await getProviderConfig('exchangeratehost')
        const parts = splitFxSymbol(src.provider_symbol)
        if (!parts) {
          socket.emit('error', { message: 'invalid_fx_symbol', symbol: src.provider_symbol })
          return
        }
        startFxPolling(key, prov?.base_url, parts.from, parts.to, resolution)
      } else if (src.provider === 'twelvedata') {
        const ws = twelveDataStream(src.provider_symbol, resolution)
        if (!ws) {
          socket.emit('error', { message: 'provider_not_implemented', provider: src.provider })
          return
        }
        ws.onmessage = (evt) => {
          // map payload to {time,open,high,low,close,volume}
        }
        ws.onerror = () => {}
        ws.onclose = () => { wsByRoom.delete(key) }
        wsByRoom.set(key, ws)
      } else if (src.provider === 'finnhub') {
        const ws = finnhubStream(src.provider_symbol)
        if (!ws) {
          socket.emit('error', { message: 'provider_not_implemented', provider: src.provider })
          return
        }
        ws.onmessage = (evt) => {
          // map finnhub payload
        }
        ws.onerror = () => {}
        ws.onclose = () => { wsByRoom.delete(key) }
        wsByRoom.set(key, ws)
      } else {
        socket.emit('error', { message: 'provider_not_implemented', provider: src.provider })
      }
    } catch (e) {
      socket.emit('error', { message: String(e?.message || e) })
    }
  })

  socket.on('unsubscribe', ({ pairId, resolution = '1' }) => {
    const key = `${pairId}:${resolution}`
    socket.leave(key)
    if (io.sockets.adapter.rooms.get(key)?.size) return
    const ws = wsByRoom.get(key)
    if (ws) {
      try { ws.close() } catch {}
      wsByRoom.delete(key)
    }
    const t = timerByRoom.get(key)
    if (t) {
      clearInterval(t)
      timerByRoom.delete(key)
    }
  })
})

const PORT = Number(process.env.QUOTES_SOCKET_PORT || 3100)
httpServer.listen(PORT, () => {
  console.log(`quotes socket listening on ${PORT}`)
})


