"""
yfinance microservice — REST API over Yahoo Finance data.

Endpoints:
  GET /bars?symbol=AAPL&resolution=1D&from_ts=1700000000&to_ts=1700999999
  GET /search?q=apple
  GET /validate?symbol=AAPL
  GET /health
"""

import os
from datetime import datetime, timezone

import yfinance as yf
from fastapi import FastAPI, HTTPException, Query
from fastapi.responses import JSONResponse

app = FastAPI(title="yfinance-api", version="1.0.0")

# TradingView resolution → yfinance interval
RESOLUTION_MAP = {
    "1":    "1m",
    "2":    "2m",
    "5":    "5m",
    "15":   "15m",
    "30":   "30m",
    "60":   "60m",
    "90":   "90m",
    "1H":   "60m",
    "2H":   "60m",   # yfinance нет 2h, используем 1h
    "4H":   "60m",   # yfinance нет 4h, используем 1h
    "240":  "60m",
    "1D":   "1d",
    "D":    "1d",
    "1440": "1d",
    "1W":   "1wk",
    "1M":   "1mo",
}


def ts_to_dt(ts: int) -> str:
    """Unix timestamp → ISO8601 string для yfinance."""
    return datetime.fromtimestamp(ts, tz=timezone.utc).strftime("%Y-%m-%d")


@app.get("/health")
def health():
    return {"status": "ok"}


@app.get("/bars")
def bars(
    symbol: str = Query(..., description="Тикер, напр. AAPL, GC=F, EURUSD=X"),
    resolution: str = Query("1D", description="TradingView resolution"),
    from_ts: int = Query(..., description="Unix timestamp начала"),
    to_ts: int = Query(..., description="Unix timestamp конца"),
):
    interval = RESOLUTION_MAP.get(resolution, "1d")

    try:
        ticker = yf.Ticker(symbol)
        df = ticker.history(
            interval=interval,
            start=ts_to_dt(from_ts),
            end=ts_to_dt(to_ts + 86400),  # +1 день чтобы включить крайний день
            auto_adjust=True,
            prepost=False,
        )
    except Exception as e:
        raise HTTPException(status_code=502, detail=f"yfinance error: {e}")

    if df is None or df.empty:
        return JSONResponse({"bars": []})

    result = []
    for idx, row in df.iterrows():
        # idx — DatetimeTZDtype или Timestamp
        try:
            ts_ms = int(idx.timestamp() * 1000)
        except Exception:
            continue
        result.append({
            "time":   ts_ms,
            "open":   round(float(row["Open"]),   8),
            "high":   round(float(row["High"]),   8),
            "low":    round(float(row["Low"]),    8),
            "close":  round(float(row["Close"]),  8),
            "volume": round(float(row["Volume"]), 2),
        })

    return JSONResponse({"bars": result})


@app.get("/search")
def search(q: str = Query(..., min_length=1)):
    """Поиск тикеров через yfinance (скрейпинг Yahoo Search)."""
    try:
        results = yf.Search(q, max_results=10)
        quotes = results.quotes if hasattr(results, "quotes") else []
    except Exception as e:
        raise HTTPException(status_code=502, detail=str(e))

    out = []
    for item in quotes:
        out.append({
            "symbol": item.get("symbol", ""),
            "name":   item.get("longname") or item.get("shortname", ""),
            "type":   item.get("quoteType", ""),
            "exchange": item.get("exchange", ""),
        })
    return JSONResponse({"results": out})


@app.get("/validate")
def validate(symbol: str = Query(...)):
    """Проверяет, существует ли тикер."""
    try:
        ticker = yf.Ticker(symbol)
        info = ticker.fast_info
        price = getattr(info, "last_price", None)
        valid = price is not None and float(price) > 0
    except Exception:
        valid = False
    return JSONResponse({"symbol": symbol, "valid": valid})


if __name__ == "__main__":
    import uvicorn
    port = int(os.environ.get("YFINANCE_PORT", 8001))
    uvicorn.run("main:app", host="0.0.0.0", port=port, reload=False)
