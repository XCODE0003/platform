const FIAT_LIKE = ['USD', 'USDT', 'EUR', 'RUB'];

/**
 * Decimal places for display: 2 for fiat-like symbols, 8 for crypto.
 */
export function amountDecimals(symbol) {
    if (!symbol) return 2;
    const s = String(symbol).toUpperCase();
    return FIAT_LIKE.includes(s) ? 2 : 8;
}

/**
 * Round amount for UI (e.g. 10000.0000000000 → "10000.00" for USD).
 */
export function formatAmount(value, symbol) {
    const n = Number(value ?? 0);
    const d = amountDecimals(symbol);
    return Number.isFinite(n) ? n.toFixed(d) : (0).toFixed(d);
}
