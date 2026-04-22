<script setup>
import { onMounted, onBeforeUnmount, ref, watch, nextTick } from 'vue'
import UnifiedDatafeed, { unsubscribeAll } from './datafeed_unified'

const props = defineProps({
  symbol: { type: String, default: 'BTCUSDT' },
  description: { type: String, default: '123' },
  interval: { type: String, default: '1' },
  theme: { type: String, default: 'dark' },
  autosize: { type: Boolean, default: true },
  currentPrice: { type: [Number, String], default: null },
  position: { type: Object, default: null },       // { id, price, side, amount, created_at, take_profit, stop_loss }
  closedPosition: { type: Object, default: null },  // { entry_price, close_price, side, amount, closed_at }
  historicalTrade: { type: Object, default: null }, // Position row from history { entry_price, close_price, side, created_at, updated_at }
})

const STORAGE_KEY_CHART_TYPE = 'platform.trade.chartType'

function intervalKey(symbol) {
  return 'platform.trade.chartInterval.' + String(symbol || '')
}
function getSavedInterval(symbol) {
  try { return localStorage.getItem(intervalKey(symbol)) || props.interval } catch { return props.interval }
}
function saveInterval(symbol, v) {
  try { if (v) localStorage.setItem(intervalKey(symbol), String(v)) } catch {}
}
function getSavedChartType() {
  try { const v = localStorage.getItem(STORAGE_KEY_CHART_TYPE); return v ? Number(v) : null } catch { return null }
}
function saveChartType(v) {
  try { localStorage.setItem(STORAGE_KEY_CHART_TYPE, String(v)) } catch {}
}

const containerRef = ref(null)
let tvWidget = null
let hideTimer = null
let lastPositionKey = null
let lastHistKey = null
let lastCurrentPrice = null
/** createShape returns EntityId — use removeEntity(id), not .remove() */
let currentPriceEntityId = null
/** Bumps on each overlay draw — stale async createShape completions are ignored */
let overlayPaintGen = 0
let chartLogoObserver = null
const isReady = ref(false)

function disconnectLogoObserver() {
  if (chartLogoObserver) {
    try { chartLogoObserver.disconnect() } catch (_) {}
    chartLogoObserver = null
  }
}

/** Скрывает иконки символов с CDN TradingView (featuresets их не отключают). */
function installSymbolLogoHiding(root) {
  if (!root || typeof MutationObserver === 'undefined') return
  disconnectLogoObserver()
  const strip = () => {
    root.querySelectorAll('img').forEach((img) => {
      const src = String(img.getAttribute('src') || img.src || '')
      if (src.includes('s3-symbol-logo') || src.includes('symbol-logo')) {
        img.style.setProperty('display', 'none', 'important')
      }
    })
  }
  strip()
  chartLogoObserver = new MutationObserver(strip)
  chartLogoObserver.observe(root, { childList: true, subtree: true })
}

const TV_SCRIPT_ID = 'tv-charting-library'

function loadTradingViewScript() {
  return new Promise((resolve, reject) => {
    if (window.TradingView && window.TradingView.widget) { resolve(); return }
    let script = document.getElementById(TV_SCRIPT_ID)
    if (script) {
      script.addEventListener('load', () => resolve())
      script.addEventListener('error', (e) => reject(e))
      return
    }
    script = document.createElement('script')
    script.id = TV_SCRIPT_ID
    script.src = '/charting_library/charting_library.js'
    script.async = true
    script.onload = () => resolve()
    script.onerror = (e) => reject(e)
    document.head.appendChild(script)
  })
}

function loadWidget() {
  if (!containerRef.value) return
  const tv = window.TradingView
  if (!tv || !tv.widget) return
  disconnectLogoObserver()
  isReady.value = false
  lastPositionKey = null
  lastHistKey = null
  lastCurrentPrice = null
  currentPriceEntityId = null
  const options = {
    symbol: props.symbol,
    datafeed: UnifiedDatafeed,
    container: containerRef.value,
    library_path: '/charting_library/',
    interval: getSavedInterval(props.symbol),
    locale: 'en',
    disabled_features: [
      'use_localstorage_for_settings',
      'header_symbol_search',
      'symbol_search_hot_key',
      'header_compare',
      'show_symbol_logos',
      'show_exchange_logos',
      'show_symbol_logo_in_legend',
      'show_symbol_logo_for_compare_studies',
    ],
    enabled_features: [],
    charts_storage_url: 'https://saveload.tradingview.com',
    charts_storage_api_version: '1.1',
    drawings_access: { type: 'black', tools: [{ name: 'Icon' }] },
    client_id: 'tradingview.com',
    user_id: 'public_user_id',
    fullscreen: false,
    autosize: props.autosize,
    studies_overrides: {},
    supports_marks: false,
    supports_timescale_marks: false,
    theme: props.theme,
    custom_css_url: '/css/tv-chart-overrides.css',
    overrides: {
      'mainSeriesProperties.statusViewStyle.showInterval': true,
      'mainSeriesProperties.statusViewStyle.symbolTextSource': 'ticker',
    },
  }
  tvWidget = new tv.widget(options)
  tvWidget.onChartReady(() => {
    isReady.value = true
    installSymbolLogoHiding(containerRef.value)
    try {
      const savedType = getSavedChartType()
      if (savedType !== null) tvWidget.chart().setChartType(savedType)
    } catch (_) {}
    try {
      const sym = props.symbol
      tvWidget.chart().onIntervalChanged().subscribe(null, (interval) => { saveInterval(sym, interval) })
    } catch (_) {}
    try {
      tvWidget.chart().onChartTypeChanged().subscribe(null, (type) => { saveChartType(type) })
    } catch (_) {}
    drawPosition()
    drawClosedTrade()
    drawHistoricalTrade()
  })
}

function getChart() {
  try {
    if (!tvWidget) return null
    // Docs use activeChart(); chart(0) should match for single layout
    const ac = tvWidget.activeChart?.()
    if (ac) return ac
    return tvWidget.chart?.() ?? null
  } catch {
    return null
  }
}

/** removeAllShapes() alone can miss async-created lines — second pass via getAllShapes */
function clearChartOverlays(chart) {
  if (!chart) return
  try { chart.removeAllShapes() } catch (_) {}
  try {
    const shapes = typeof chart.getAllShapes === 'function' ? chart.getAllShapes() : []
    for (const s of shapes) {
      try { chart.removeEntity(s.id) } catch (_) {}
    }
  } catch (_) {}
}

/** createShape may return EntityId or Promise<EntityId> (runtime) */
async function createShapeAsync(chart, point, options) {
  const raw = chart.createShape(point, options)
  return await Promise.resolve(raw)
}

function clearAll() {
  const chart = getChart()
  clearChartOverlays(chart)
  lastPositionKey = null
  lastHistKey = null
  lastCurrentPrice = null
  currentPriceEntityId = null
  if (hideTimer) { clearTimeout(hideTimer); hideTimer = null }
}

async function drawCurrentPriceAsync(chart, gen) {
  const cp = Number(props.currentPrice) || null
  if (!cp || !Number.isFinite(cp)) return
  if (gen != null && gen !== overlayPaintGen) return
  if (cp === lastCurrentPrice && currentPriceEntityId) return
  if (currentPriceEntityId) {
    try { chart.removeEntity(currentPriceEntityId) } catch (_) {}
    currentPriceEntityId = null
  }
  lastCurrentPrice = cp
  const now = Math.floor(Date.now() / 1000)
  try {
    const id = await createShapeAsync(chart, { time: now, price: cp }, {
      shape: 'horizontal_line', disableSelection: true, lock: true,
      text: `Now ${cp}`,
      overrides: { linecolor: '#787b86', linewidth: 1, linestyle: 2 },
    })
    if (gen != null && gen !== overlayPaintGen) return
    currentPriceEntityId = id ?? null
  } catch (_) {}
}

async function drawPositionAsync() {
  const gen = ++overlayPaintGen
  if (!tvWidget || !isReady.value) return
  const chart = getChart()
  if (!chart) return

  if (!props.position || !props.position.price) {
    if (lastPositionKey) clearAll()
    return
  }

  const price = Number(props.position.price)
  const side = props.position.side || 'buy'
  const posId = String(props.position.id ?? 'x')
  const newKey = String(props.symbol) + '|' + posId + '|' + side + '|' + price

  if (lastPositionKey === newKey) {
    await drawCurrentPriceAsync(chart, gen)
    return
  }

  clearAll()
  if (gen !== overlayPaintGen) return
  lastPositionKey = newKey

  const isBuy = side === 'buy'
  const color = isBuy ? '#79F995' : '#F44B4B'
  const entryTime = props.position.created_at
    ? Math.floor(new Date(props.position.created_at).getTime() / 1000)
    : Math.floor(Date.now() / 1000)

  try {
    await createShapeAsync(chart, { time: entryTime, price }, {
      shape: 'horizontal_line', disableSelection: true, lock: true,
      text: `${side.toUpperCase()} ${props.position.amount || ''}`.trim(),
      overrides: { linecolor: color, linewidth: 2 },
    })
    if (gen !== overlayPaintGen) return
    await createShapeAsync(chart, { time: entryTime, price }, {
      shape: isBuy ? 'arrow_up' : 'arrow_down',
      text: 'Entry', overrides: { color },
      disableSelection: true, lock: true,
    })
  } catch (e) { console.warn('[TvChart] entry shapes error', e) }
  if (gen !== overlayPaintGen) return

  const tp = Number(props.position.take_profit) || null
  if (tp && Number.isFinite(tp)) {
    try {
      await createShapeAsync(chart, { time: entryTime, price: tp }, {
        shape: 'horizontal_line', disableSelection: true, lock: true,
        text: `Take Profit — ${tp.toFixed(4)}`,
        overrides: {
          linecolor: '#79F995', linewidth: 1, linestyle: 1,
          textcolor: '#79F995', fontsize: 12, bold: true,
          horzLabelsAlign: 'right',
        },
      })
    } catch (_) {}
  }
  if (gen !== overlayPaintGen) return

  const sl = Number(props.position.stop_loss) || null
  if (sl && Number.isFinite(sl)) {
    try {
      await createShapeAsync(chart, { time: entryTime, price: sl }, {
        shape: 'horizontal_line', disableSelection: true, lock: true,
        text: `Stop Loss — ${sl.toFixed(4)}`,
        overrides: {
          linecolor: '#F44B4B', linewidth: 1, linestyle: 1,
          textcolor: '#F44B4B', fontsize: 12, bold: true,
          horzLabelsAlign: 'right',
        },
      })
    } catch (_) {}
  }
  if (gen !== overlayPaintGen) return

  await drawCurrentPriceAsync(chart, gen)
}

function drawPosition() {
  void drawPositionAsync()
}

async function drawClosedTradeAsync() {
  const gen = ++overlayPaintGen
  if (!tvWidget || !isReady.value) return
  const chart = getChart()
  if (!chart) return
  const closedAt = props.closedPosition?.closed_at || props.closedPosition?.updated_at
  if (!props.closedPosition?.close_price || !closedAt) return

  const closePrice = Number(props.closedPosition.close_price)
  const side = props.closedPosition.side || 'buy'
  const isBuy = side === 'buy'
  const closeTime = Math.floor(new Date(closedAt).getTime() / 1000)
  const color = isBuy ? '#79F995' : '#F44B4B'

  try {
    await createShapeAsync(chart, { time: closeTime, price: closePrice }, {
      shape: isBuy ? 'arrow_down' : 'arrow_up',
      text: 'Exit', overrides: { color },
      disableSelection: true, lock: true,
    })
  } catch (e) { console.warn('[TvChart] exit arrow error', e) }
  if (gen !== overlayPaintGen) return

  if (hideTimer) clearTimeout(hideTimer)
  hideTimer = setTimeout(() => clearAll(), 10000)
}

function drawClosedTrade() {
  void drawClosedTradeAsync()
}

async function drawHistoricalTradeAsync() {
  const gen = ++overlayPaintGen
  if (!tvWidget || !isReady.value) return
  const chart = getChart()
  if (!chart) return

  const t = props.historicalTrade
  if (!t?.entry_price || !t?.close_price) {
    if (lastHistKey) clearAll()
    drawPosition()
    return
  }

  const entryPrice = Number(t.entry_price)
  const closePrice = Number(t.close_price)
  if (!Number.isFinite(entryPrice) || !Number.isFinite(closePrice)) {
    if (lastHistKey) clearAll()
    drawPosition()
    return
  }

  const newKey = String(t.id) + '|' + entryPrice + '|' + closePrice
  if (lastHistKey === newKey) return

  clearAll()
  if (gen !== overlayPaintGen) return
  lastHistKey = newKey

  const side = t.side || 'buy'
  const isBuy = side === 'buy'
  const color = isBuy ? '#79F995' : '#F44B4B'
  const entryTime = t.created_at
    ? Math.floor(new Date(t.created_at).getTime() / 1000)
    : Math.floor(Date.now() / 1000)
  const exitTime = (t.closed_at || t.updated_at)
    ? Math.floor(new Date(t.closed_at || t.updated_at).getTime() / 1000)
    : entryTime + 60

  try {
    await createShapeAsync(chart, { time: entryTime, price: entryPrice }, {
      shape: 'horizontal_line', disableSelection: true, lock: true,
      text: `${side.toUpperCase()} entry`,
      overrides: { linecolor: color, linewidth: 1, linestyle: 2 },
    })
    if (gen !== overlayPaintGen) return
    await createShapeAsync(chart, { time: entryTime, price: entryPrice }, {
      shape: isBuy ? 'arrow_up' : 'arrow_down',
      text: 'Entry', overrides: { color },
      disableSelection: true, lock: true,
    })
    if (gen !== overlayPaintGen) return
    await createShapeAsync(chart, { time: exitTime, price: closePrice }, {
      shape: isBuy ? 'arrow_down' : 'arrow_up',
      text: 'Exit', overrides: { color },
      disableSelection: true, lock: true,
    })
  } catch (e) { console.warn('[TvChart] drawHistoricalTrade error', e) }
}

function drawHistoricalTrade() {
  void drawHistoricalTradeAsync()
}

onMounted(async () => {
  try {
    await loadTradingViewScript()
    loadWidget()
  } catch (e) {}
})

onBeforeUnmount(() => {
  disconnectLogoObserver()
  unsubscribeAll()
  clearAll()
  if (tvWidget && tvWidget.remove) tvWidget.remove()
})

watch(() => [props.symbol, props.interval, props.theme, props.autosize], async () => {
  unsubscribeAll()
  clearAll()
  if (tvWidget && tvWidget.remove) tvWidget.remove()
  tvWidget = null
  isReady.value = false
  if (!window.TradingView || !window.TradingView.widget) {
    try { await loadTradingViewScript() } catch (_) {}
  }
  loadWidget()
})

watch(() => props.position, () => {
  drawPosition()
}, { deep: true })

watch(() => props.currentPrice, () => {
  if (!tvWidget || !isReady.value || !props.position) return
  const chart = getChart()
  if (!chart) return
  void drawCurrentPriceAsync(chart, null)
})

watch(() => props.closedPosition, () => {
  // After position cleared, drawPosition runs first and removeAllShapes; defer exit arrow
  nextTick(() => drawClosedTrade())
}, { deep: true })

watch(() => props.historicalTrade, () => {
  drawHistoricalTrade()
}, { deep: true })
</script>

<template>
  <div ref="containerRef" style="width: 100%; height: 100%; min-height: 400px; background-color: black;"></div>
</template>

<style scoped>
</style>
