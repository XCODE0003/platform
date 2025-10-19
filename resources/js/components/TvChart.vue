<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import UnifiedDatafeed from './datafeed_unified'

const props = defineProps({
  symbol: { type: String, default: 'BTCUSDT' },
  interval: { type: String, default: '5' },
  theme: { type: String, default: 'dark' },
  autosize: { type: Boolean, default: true },
})

const containerRef = ref(null)
let tvWidget = null

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
  if (tvWidget && tvWidget.remove) tvWidget.remove()
})

watch(() => [props.symbol, props.interval, props.theme, props.autosize], async () => {
  if (tvWidget && tvWidget.remove) tvWidget.remove()
  tvWidget = null
  if (!window.TradingView || !window.TradingView.widget) {
    try {
      await loadTradingViewScript()
    } catch (_) {}
  }
  loadWidget()
})
</script>

<template>
  <div ref="containerRef" style="width: 100%; height: 100%; min-height: 400px; background-color: black;"></div>
  </template>

<style scoped>
</style>


