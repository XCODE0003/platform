<script setup>
import { onMounted, ref } from 'vue';


const pair = ref('BTC_USDT');

function loadTradingViewScript() {
    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js';
    script.async = true;

    // Configuration object
    const config = {
        "autosize": true,
        "symbol": pair.value.replace('_', ''),
        "interval": "M",
        "timezone": "Etc/UTC",
        "theme": "dark",
        "style": "1",
        "locale": "en",
        "enable_publishing": false,
        "hide_legend": true,
        "withdateranges": true,
        "hide_side_toolbar": false,
        "save_image": false,
        "backgroundColor": "#061B27",
        "support_host": "https://www.tradingview.com"
    };

    script.textContent = JSON.stringify(config);

    document.body.appendChild(script);
}

onMounted(() => {
    loadTradingViewScript();
});
</script>

<template>
    <main class="trade h100">
        <section class="trade">
            <div class="container trade-container">
                <div class="trade-content">
                    <div class="trade-left">
                        <div class="pair-head">
                            <div class="left">
                                <div class="itc-select" id="select-1">

                                </div>
                                <div class="pair-price">
                                    <span class="title text_small_14 color-gray2">Price</span>
                                    <span class="text_17 color-green transition_trade" id="valueInfo_price">
                                        <span class="loader"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="right">
                                <div class="pair-info">
                                    <div class="pair-change">
                                        <span class="title text_small_14 color-gray2">24h change</span>
                                        <span class="text_17 color-green transition_trade" id="valueInfo_change">
                                            <span class="loader"></span>
                                        </span>
                                    </div>
                                    <div class="pair-high">
                                        <span class="title text_small_14 color-gray2">24h high</span>
                                        <span class="text_17 color-black transition_trade" id="valueInfo_high">
                                            <span class="loader"></span>
                                        </span>
                                    </div>
                                    <div class="pair-low">
                                        <span class="title text_small_14 color-gray2">24h low</span>
                                        <span class="text_17 color-black transition_trade" id="valueInfo_low"><span class="loader"></span></span>
                                    </div>
                                    <div class="pair-volume">
                                        <span class="title text_small_14 color-gray2">24h volume (BTC)</span>
                                        <span class="text_17 color-black transition_trade" id="valueInfo_volume"><span class="loader"></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <!-- TradingView Widget BEGIN -->
                            <div class="tradingview-widget-container" style="height:100%;width:100%; border-radius: 10px">
                                <div class="tradingview-widget-container__widget" style="height:100%;width:100%; ">
                                </div>



                            </div>
                            <!-- TradingView Widget END -->
                        </div>
                        <div class="trade-history">
                            <div class="tabs tabs-1">
                                <div class="tabs__header tabs__header-1">
                                    <div class="tabs__header-item tabs__header-item-1 active">
                                        Open orders
                                    </div>
                                    <div class="tabs__header-item tabs__header-item-1">
                                        Trade history
                                    </div>
                                </div>
                                <div class="tabs__content tabs__content-1">
                                    <div class="tabs__content-item tabs__content-item-1 openOrders">
                                        <div class="grid-head">
                                            <div>Date, time</div>
                                            <div>Pair</div>
                                            <div>Type</div>
                                            <div>Side</div>
                                            <div>Price</div>
                                            <div>Quantity</div>
                                            <div>Total (USDT)</div>
                                            <div>Cancel</div>
                                        </div>
                                        <div class="overflow" id="openOrders">

                                            <div class="grid-line ">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>18,156.16</div>

                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>18,156.16</div>
                                                <div>
                                                    <button class="clear cancel-btn">
                                                        <img src="/images/cancel-icon.svg" alt="x" />
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>
                                                    <button class="clear cancel-btn">
                                                        <img src="/images/cancel-icon.svg" alt="x" />
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>
                                                    <button class="clear cancel-btn">
                                                        <img src="/images/cancel-icon.svg" alt="x" />
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>
                                                    <button class="clear cancel-btn">
                                                        <img src="/images/cancel-icon.svg" alt="x" />
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>
                                                    <button class="clear cancel-btn">
                                                        <img src="/images/cancel-icon.svg" alt="x" />
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>
                                                    <button class="clear cancel-btn">
                                                        <img src="/images/cancel-icon.svg" alt="x" />
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>
                                                    <button class="clear cancel-btn">
                                                        <img src="/images/cancel-icon.svg" alt="x" />
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>
                                                    <button class="clear cancel-btn">
                                                        <img src="/images/cancel-icon.svg" alt="x" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tabs__content-item tabs__content-item-1">
                                        <div class="grid-head">
                                            <div>Date, time</div>
                                            <div>Pair</div>
                                            <div>Type</div>
                                            <div>Side</div>
                                            <div>Price</div>
                                            <div>Quantity</div>
                                            <div>Total (USDT)</div>
                                            <div>Status</div>
                                        </div>
                                        <div class="overflow" id="closedOrders">
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>18,156.16</div>
                                                <div>Completed</div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>18,156.16</div>
                                                <div>Closed</div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>Closed</div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>Closed</div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>Closed</div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>Closed</div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>Closed</div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>Closed</div>
                                            </div>
                                            <div class="grid-line">
                                                <div>05/21/23, 13:43:57</div>
                                                <div>BTC/USDT</div>
                                                <div>Limit</div>
                                                <div>Buy</div>
                                                <div>26,481.13</div>
                                                <div>0.685630</div>
                                                <div>200,6808624</div>
                                                <div>Closed</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="trade-right">
                        <div class="trade-table">
                            <div class="tabs tabs-2">
                                <div class="tabs__header tabs__header-2">
                                    <div class="tabs__header-item tabs__header-item-2 active">
                                        Recent trades
                                    </div>
                                    <div class="tabs__header-item tabs__header-item-2">
                                        Order book
                                    </div>
                                </div>
                                <div class="tabs__content tabs__content-2">
                                    <div class="tabs__content-item tabs__content-item-2">
                                        <div class="grid-head">
                                            <div>Price (USDT)</div>
                                            <div>Quantity</div>
                                            <div>Time</div>
                                        </div>
                                        <div id="recentTrade">

                                        </div>
                                    </div>
                                    <div class="tabs__content-item tabs__content-item-2 orderBook">
                                        <div class="grid-head">
                                            <div>Price (USDT)</div>
                                            <div>Quantity</div>
                                            <div>Total (USDT)</div>
                                        </div>
                                        <div id="OrderBookBuy">



                                            <div class="grid-line green create">
                                                <div class="bg" style="width: 80%"></div>
                                                <div class="color-green2">31,113.04</div>
                                                <div class="color-white">0.229460</div>
                                                <div class="color-gray2">15:23:57</div>
                                            </div>
                                            <div class="grid-line green create">
                                                <div class="bg" style="width: 80%"></div>
                                                <div class="color-green2">31,113.04</div>
                                                <div class="color-white">0.229460</div>
                                                <div class="color-gray2">15:23:57</div>
                                            </div>
                                            <div class="grid-line green create">
                                                <div class="bg" style="width: 80%"></div>
                                                <div class="color-green2">31,113.04</div>
                                                <div class="color-white">0.229460</div>
                                                <div class="color-gray2">15:23:57</div>
                                            </div>
                                            <div class="grid-line green create">
                                                <div class="bg" style="width: 80%"></div>
                                                <div class="color-green2">31,113.04</div>
                                                <div class="color-white">0.229460</div>
                                                <div class="color-gray2">15:23:57</div>
                                            </div>
                                            <div class="grid-line gree create">
                                                <div class="bg" style="width: 80%"></div>
                                                <div class="color-green2">31,113.04</div>
                                                <div class="color-white">0.229460</div>
                                                <div class="color-gray2">15:23:57</div>
                                            </div>
                                        </div>
                                        <div class="grid-separator">
                                            <span class="text_17 color-green flex-center" id="valueInfo_price1">


                                            </span>

                                        </div>
                                        <div id="OrderBookSell">
                                            <div class="grid-line create red">

                                            </div>
                                            <div class="grid-line red">
                                                <div class="bg" style="width: 80%"></div>
                                                <div class="color-red">31,113.04</div>
                                                <div class="color-white">0.229460</div>
                                                <div class="color-gray2">15:23:57</div>
                                            </div>
                                            <div class="grid-line red">
                                                <div class="bg" style="width: 80%"></div>
                                                <div class="color-red">31,113.04</div>
                                                <div class="color-white">0.229460</div>
                                                <div class="color-gray2">15:23:57</div>
                                            </div>
                                            <div class="grid-line red">
                                                <div class="bg" style="width: 80%"></div>
                                                <div class="color-red">31,113.04</div>
                                                <div class="color-white">0.229460</div>
                                                <div class="color-gray2">15:23:57</div>
                                            </div>
                                            <div class="grid-line red">
                                                <div class="bg" style="width: 80%"></div>
                                                <div class="color-red">31,113.04</div>
                                                <div class="color-white">0.229460</div>
                                                <div class="color-gray2">15:23:57</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="order-form">
                            <div class="tabs tabs-3">
                                <div class="tabs__header tabs__header-3">
                                    <div class="tabs__header-item tabs__header-item-3 active text_16 buy">
                                        Buy
                                    </div>
                                    <div class="tabs__header-item tabs__header-item-3 text_16 sell">
                                        Sell
                                    </div>
                                </div>
                                <div class="tabs__content tabs__content-3">
                                    <form id="CreateOrderBuy">
                                        <input hidden="" name="type_order" value="market">
                                        <div class="tabs__content-item tabs__content-item-3" id="LimitOrderBuy">

                                            <div class="flex-center">
                                                <div class="way-select">
                                                    <button type="button" class="btn way text_small_14">Limit</button>
                                                    <button type="button" class="btn way text_small_14 active">
                                                        Market
                                                    </button>
                                                </div>
                                                <div class="balance">
                                                    <span class="text_small_12 color-gray2">Available balance</span>
                                                    <span class="balanceUsdt text_16 color-blue">120 USDT</span>
                                                </div>
                                            </div>
                                            <label class="order-label">
                                                <span class="text_small_12 color-dark">Market price</span>
                                                <input type="text" disabled id="quantityPriceBuy" class="order-input text_17" value="≈ ?" />


                                                <span class="сurrency text_17 color-gray2">BTC</span>
                                            </label>
                                            <label class="order-label">
                                                <span class="text_small_12 color-dark">Quantity</span>
                                                <input type="text" id="quantityBuy" name="amount" oninput="delayedCalculateBuy()" class="order-input  text_17" placeholder="0" />
                                                <span class="сurrency text_17 color-gray2">USDT</span>
                                            </label>

                                            <!-- <button class="btn btn-buy btn_16 notauth">Buy</button> -->
                                            <button type="submit" class="btn btn-buy btn_16">Buy</button>

                                        </div>
                                    </form>
                                    <form id="CreateOrderSell">
                                        <input hidden="" name="type_order" value="market">
                                        <div class="tabs__content-item tabs__content-item-3" id="LimitOrderSell">
                                            <div class="flex-center">
                                                <div class="way-select">
                                                    <button type="button" onclick="changeTypeOrder('LimitOrderSell', 'limit', 'sell')" class="btn way text_small_14 ">Limit</button>
                                                    <button type="button" onclick="changeTypeOrder('LimitOrderSell', 'market', 'sell')" class="btn way text_small_14 active">Market</button>
                                                </div>
                                                <div class="balance">
                                                    <span class="text_small_12 color-gray2">Available balance</span>
                                                    <span class="balanceCoin text_16 color-blue">ASDASD</span>
                                                </div>
                                            </div>
                                            <label class="order-label">
                                                <span class="text_small_12 color-dark">Market price</span>
                                                <input type="text" disabled id="quantityPriceSell" class="order-input text_17" value="≈ ?" />
                                                <span class="сurrency text_17 color-gray2">USDT</span>
                                            </label>
                                            <label class="order-label">
                                                <span class="text_small_12 color-dark">Quantity</span>
                                                <input type="text" id="quantitySell" name="amount" oninput="delayedCalculateSell()" class="order-input text_17" placeholder="0" value="" />
                                                <span class="сurrency text_17 color-gray2">ASDAS</span>
                                            </label>

                                            <button class="btn btn-sell btn_16">Sell</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>

<style scoped>
.tradingview-widget-container iframe {
    border-radius: 10px;

}

.grid-line {
    opacity: 0;
    transform: translateY(-20px);
    transition: opacity 0.5s ease, transform 0.5s ease;
}

.grid-line.active {
    opacity: 1;
    transform: translateY(0);
}

.grid-line.create {
    opacity: 1;
    transform: translateY(0);
}
</style>