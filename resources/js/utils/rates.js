const calculateRate = (amount, exchange_rate) => {
    return amount * exchange_rate;
};
const calculateInUsd = (amount, exchange_rate) => {
    return amount / exchange_rate;
};
export { calculateInUsd, calculateRate };
