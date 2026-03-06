<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import UnifiedDatafeed, { unsubscribeAll } from './datafeed_unified'

const props = defineProps({
  symbol: { type: String, default: 'BTCUSDT' },
  interval: { type: String, default: '1' },
  theme: { type: String, default: 'dark' },
  autosize: { type: Boolean, default: true },
  position: { type: Object, default: null }, // { price, side, amount, created_at }
  closedPosition: { type: Object, default: null }, // { entry_price, close_price, side, amount, closed_at }
})

const containerRef = ref(null)
let tvWidget = null
let positionShape = null
let entryArrow = null
let exitArrow = null
let hideTimer = null
let lastPositionKey = null
let lastExitKey = null
const isReady = ref(false)

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
  isReady.value = false
  // reset memoized keys on new widget
  lastPositionKey = null
  lastExitKey = null
  const options = {
    symbol: props.symbol,
    datafeed: UnifiedDatafeed,
    container: containerRef.value,
    library_path: '/charting_library/',
    interval: props.interval,
    locale: 'en',
    disabled_features: [
      'use_localstorage_for_settings',
      'header_symbol_search',
      'symbol_search_hot_key',
    ],
    enabled_features: [],
    charts_storage_url: 'https://saveload.tradingview.com',
    charts_storage_api_version: '1.1',
    client_id: 'tradingview.com',
    user_id: 'public_user_id',
    fullscreen: false,
    autosize: props.autosize,
    studies_overrides: {},
    supports_marks: false,
    supports_timescale_marks: false,
    theme: props.theme,
    overrides: {
      'mainSeriesProperties.statusViewStyle.showInterval': true,
      'mainSeriesProperties.statusViewStyle.symbolTextSource': 'ticker',
    },
  }
  tvWidget = new tv.widget(options)
  tvWidget.onChartReady(() => {
    isReady.value = true
    drawPosition()
    drawClosedTrade()
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

      // Horizontal line at entry price
      positionShape = chart.createShape({
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

      // Entry arrow (at time of creation if available)
      if (props.position.created_at) {
        const entryTime = Math.floor(new Date(props.position.created_at).getTime() / 1000)
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
      }
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
    if (!props.closedPosition || !props.closedPosition.close_price || !props.closedPosition.closed_at) {
      if (exitArrow && exitArrow.remove) { exitArrow.remove(); exitArrow = null }
      lastExitKey = null
      return
    }

    // Draw exit arrow for last closed position
    if (props.closedPosition && props.closedPosition.close_price && props.closedPosition.closed_at) {
      const closePrice = Number(props.closedPosition.close_price)
      const side = props.closedPosition.side || 'buy'
      const isBuy = side === 'buy'
      const closeTime = Math.floor(new Date(props.closedPosition.closed_at).getTime() / 1000)
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
  unsubscribeAll()
  try { if (positionShape && positionShape.remove) positionShape.remove() } catch (_) {}
  try { if (entryArrow && entryArrow.remove) entryArrow.remove() } catch (_) {}
  try { if (exitArrow && exitArrow.remove) exitArrow.remove() } catch (_) {}
  try { if (hideTimer) clearTimeout(hideTimer) } catch (_) {}
  lastPositionKey = null
  lastExitKey = null
  if (tvWidget && tvWidget.remove) tvWidget.remove()
})

watch(() => [props.symbol, props.interval, props.theme, props.autosize], async () => {
  unsubscribeAll()
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
</script>

<template>
  <div ref="containerRef" style="width: 100%; height: 100%; min-height: 400px; background-color: black;"></div>
  </template>

<style scoped>
</style>


