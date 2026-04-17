<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import UnifiedDatafeed, { unsubscribeAll } from './datafeed_unified'

const props = defineProps({
  symbol: { type: String, default: 'BTCUSDT' },
  description: { type: String, default: '123' },
  interval: { type: String, default: '1' },
  theme: { type: String, default: 'dark' },
  autosize: { type: Boolean, default: true },
  position: { type: Object, default: null },       // { price, side, amount, created_at }
  closedPosition: { type: Object, default: null },  // { entry_price, close_price, side, amount, closed_at }
  historicalTrade: { type: Object, default: null }, // Position row from history { entry_price, close_price, side, created_at, updated_at }
})

const STORAGE_KEY_CHART_TYPE = 'platform.trade.chartType'

function intervalKey(symbol) {
  // symbol = "PAIR:123" → key = "platform.trade.chartInterval.PAIR:123"
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
let positionShape = null
let entryArrow = null
let exitArrow = null
let hideTimer = null
let lastPositionKey = null
let lastExitKey = null
// Historical trade shapes
let histEntryLine = null
let histEntryArrow = null
let histExitArrow = null
let lastHistKey = null
let chartLogoObserver = null
const isReady = ref(false)

function disconnectLogoObserver() {
  if (chartLogoObserver) {
    try {
      chartLogoObserver.disconnect()
    } catch (_) {}
    chartLogoObserver = null
  }
}

/** Скрывает иконки символов с CDN TradingView (featuresets их не отключают). */
function installSymbolLogoHiding(root) {
  if (!root || typeof MutationObserver === 'undefined') {
    return
  }
  disconnectLogoObserver()
  const strip = () => {
    root.querySelectorAll('img').forEach((img) => {
      const src = String(img.getAttribute('src') || img.src || '')
      if (
        src.includes('s3-symbol-logo') ||
        src.includes('symbol-logo')
      ) {
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
    if (window.TradingView && window.TradingView.widget) {
      resolve()
      return
    }

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
  // reset memoized keys on new widget
  lastPositionKey = null
  lastExitKey = null
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
    /** Скрыть кнопку «Icon» (эмодзи/стикер) на панели рисования */
    drawings_access: {
      type: 'black',
      tools: [{ name: 'Icon' }],
    },
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

    // Restore chart type (candles/bars/area etc.) if previously saved
    try {
      const savedType = getSavedChartType()
      if (savedType !== null) tvWidget.chart().setChartType(savedType)
    } catch (_) {}

    // Persist interval changes per symbol
    try {
      const sym = props.symbol
      tvWidget.chart().onIntervalChanged().subscribe(null, (interval) => {
        saveInterval(sym, interval)
      })
    } catch (_) {}

    // Persist chart type changes
    try {
      tvWidget.chart().onChartTypeChanged().subscribe(null, (type) => {
        saveChartType(type)
      })
    } catch (_) {}

    drawPosition()
    drawClosedTrade()
    drawHistoricalTrade()
  })
}

function drawPosition() {
  if (!tvWidget || !isReady.value) return
  let chart
  try {
    chart = tvWidget.chart()
  } catch {
    return
  }
  try {
    // Debug
    try { console.log('[TvChart] drawPosition called', props.position) } catch (_) {}
    // Remove old shapes
    const symbolKey = String(props.symbol || '')
    if (!props.position || !props.position.price) {
      if (positionShape && positionShape.remove) { positionShape.remove(); positionShape = null }
      if (entryArrow && entryArrow.remove) { entryArrow.remove(); entryArrow = null }
      lastPositionKey = null
      return
    }
    const price = Number(props.position.price)
    const side = props.position.side || 'buy'
    const newKey = symbolKey + '|' + side + '|' + String(price)
    if (lastPositionKey === newKey && positionShape) {
      // Already drawn, skip duplicate
      return
    }
    if (positionShape && positionShape.remove) { positionShape.remove(); positionShape = null }
    if (entryArrow && entryArrow.remove) { entryArrow.remove(); entryArrow = null }

    // Draw open position line + entry arrow
    if (props.position && props.position.price) {
      const isBuy = side === 'buy'
      const color = isBuy ? '#79F995' : '#F44B4B'
      const label = `${side.toUpperCase()} ${props.position.amount || ''}`
      const entryTime = props.position.created_at
        ? Math.floor(new Date(props.position.created_at).getTime() / 1000)
        : Math.floor(Date.now() / 1000)

      // Horizontal line at entry price — time is required by some library versions
      positionShape = chart.createShape({
        time: entryTime,
        price: price,
      }, {
        shape: 'horizontal_line',
        disableSelection: true,
        lock: true,
        text: label.trim(),
        overrides: {
          linecolor: color,
          linewidth: 2,
        },
      })

      // Entry arrow at the entry candle
      entryArrow = chart.createShape(
        { time: entryTime, price: price },
        {
          shape: isBuy ? 'arrow_up' : 'arrow_down',
          text: 'Entry',
          overrides: { color: color },
          disableSelection: true,
          lock: true,
        }
      )
      lastPositionKey = newKey
    }
  } catch (e) {
    console.warn('[TvChart] drawPosition error', e)
  }
}

function drawClosedTrade() {
  if (!tvWidget || !isReady.value) return
  let chart
  try {
    chart = tvWidget.chart()
  } catch {
    return
  }
  try {
    // Debug
    try { console.log('[TvChart] drawClosedTrade called', props.closedPosition) } catch (_) {}
    // Remove old exit arrow
    const symbolKey = String(props.symbol || '')
    const closedAt = props.closedPosition?.closed_at || props.closedPosition?.updated_at
    if (!props.closedPosition || !props.closedPosition.close_price || !closedAt) {
      if (exitArrow && exitArrow.remove) { exitArrow.remove(); exitArrow = null }
      lastExitKey = null
      return
    }

    // Draw exit arrow for last closed position
    if (props.closedPosition && props.closedPosition.close_price && closedAt) {
      const closePrice = Number(props.closedPosition.close_price)
      const side = props.closedPosition.side || 'buy'
      const isBuy = side === 'buy'
      const closeTime = Math.floor(new Date(closedAt).getTime() / 1000)
      const color = isBuy ? '#79F995' : '#F44B4B'
      const newExitKey = symbolKey + '|' + side + '|' + String(closePrice) + '|' + String(closeTime)
      if (lastExitKey === newExitKey && exitArrow) {
        return
      }
      if (exitArrow && exitArrow.remove) { exitArrow.remove(); exitArrow = null }

      exitArrow = chart.createShape(
        { time: closeTime, price: closePrice },
        {
          shape: isBuy ? 'arrow_down' : 'arrow_up',
          text: 'Exit',
          overrides: { color: color },
          disableSelection: true,
          lock: true,
        }
      )
      lastExitKey = newExitKey

      // Force-hide position line and entry arrow after 10s regardless of store state
      if (hideTimer) { try { clearTimeout(hideTimer) } catch (_) {} }
      hideTimer = setTimeout(() => {
        try { console.log('[TvChart] hideTimer fired -> removing position/entry') } catch (_) {}
        try { if (positionShape && positionShape.remove) positionShape.remove() } catch (_) {}
        try { if (entryArrow && entryArrow.remove) entryArrow.remove() } catch (_) {}
        lastPositionKey = null
      }, 10000)
    }
  } catch (e) {
    console.warn('[TvChart] drawClosedTrade error', e)
  }
}

function clearHistoricalShapes() {
  try { if (histEntryLine && histEntryLine.remove) histEntryLine.remove() } catch (_) {}
  try { if (histEntryArrow && histEntryArrow.remove) histEntryArrow.remove() } catch (_) {}
  try { if (histExitArrow && histExitArrow.remove) histExitArrow.remove() } catch (_) {}
  histEntryLine = null
  histEntryArrow = null
  histExitArrow = null
  lastHistKey = null
}

function drawHistoricalTrade() {
  if (!tvWidget || !isReady.value) return
  let chart
  try { chart = tvWidget.chart() } catch { return }

  const t = props.historicalTrade
  if (!t || !t.entry_price || !t.close_price) {
    clearHistoricalShapes()
    return
  }

  const entryPrice = Number(t.entry_price)
  const closePrice = Number(t.close_price)
  if (!Number.isFinite(entryPrice) || !Number.isFinite(closePrice)) {
    clearHistoricalShapes()
    return
  }

  const newKey = String(t.id) + '|' + entryPrice + '|' + closePrice
  if (lastHistKey === newKey) return  // уже нарисовано — пропускаем

  clearHistoricalShapes()  // убираем предыдущую сделку перед рисованием новой
  lastHistKey = newKey

  const side = t.side || 'buy'
  const isBuy = side === 'buy'
  const color = isBuy ? '#79F995' : '#F44B4B'

  const entryTime = t.created_at
    ? Math.floor(new Date(t.created_at).getTime() / 1000)
    : Math.floor(Date.now() / 1000)
  const exitTime = (t.updated_at || t.closed_at)
    ? Math.floor(new Date(t.updated_at || t.closed_at).getTime() / 1000)
    : entryTime + 60

  try {
    // Horizontal line at entry price
    histEntryLine = chart.createShape(
      { time: entryTime, price: entryPrice },
      {
        shape: 'horizontal_line',
        disableSelection: true,
        lock: true,
        text: `${side.toUpperCase()} entry`,
        overrides: { linecolor: color, linewidth: 1, linestyle: 2 /* dashed */ },
      }
    )
    // Entry arrow
    histEntryArrow = chart.createShape(
      { time: entryTime, price: entryPrice },
      {
        shape: isBuy ? 'arrow_up' : 'arrow_down',
        text: 'Entry',
        overrides: { color: color },
        disableSelection: true,
        lock: true,
      }
    )
    // Exit arrow
    histExitArrow = chart.createShape(
      { time: exitTime, price: closePrice },
      {
        shape: isBuy ? 'arrow_down' : 'arrow_up',
        text: 'Exit',
        overrides: { color: color },
        disableSelection: true,
        lock: true,
      }
    )
  } catch (e) {
    console.warn('[TvChart] drawHistoricalTrade error', e)
  }
}

onMounted(async () => {
  try {
    await loadTradingViewScript()
    loadWidget()
  } catch (e) {
    // fail silently; user can check console
    // console.error('Failed to load TradingView library', e)
  }
})

onBeforeUnmount(() => {
  disconnectLogoObserver()
  unsubscribeAll()
  try { if (positionShape && positionShape.remove) positionShape.remove() } catch (_) {}
  try { if (entryArrow && entryArrow.remove) entryArrow.remove() } catch (_) {}
  try { if (exitArrow && exitArrow.remove) exitArrow.remove() } catch (_) {}
  try { if (hideTimer) clearTimeout(hideTimer) } catch (_) {}
  clearHistoricalShapes()
  lastPositionKey = null
  lastExitKey = null
  if (tvWidget && tvWidget.remove) tvWidget.remove()
})

watch(() => [props.symbol, props.interval, props.theme, props.autosize], async () => {
  unsubscribeAll()
  clearHistoricalShapes()
  if (tvWidget && tvWidget.remove) tvWidget.remove()
  tvWidget = null
  isReady.value = false
  if (!window.TradingView || !window.TradingView.widget) {
    try {
      await loadTradingViewScript()
    } catch (_) {}
  }
  loadWidget()
})

watch(() => props.position, () => {
  drawPosition()
}, { deep: true })

watch(() => props.closedPosition, () => {
  drawClosedTrade()
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


