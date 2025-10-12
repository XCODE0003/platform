// Импортируем iziToast (нужно будет установить: npm install izitoast)
import iziToast from 'izitoast';

// Ваши общие опции
const commonOptions = {
    closeOnClick: true,
    class: "toast",
    transitionIn: "fadeInDown",
    transitionOut: "fadeOutUp",
    position: "topCenter",
    iconUrl: "/images/success.svg",
    close: false,
};

export function useToast() {
    const showError = (message, title = 'Error') => {
        iziToast.error({
            ...commonOptions,

            message: message,
            iconUrl: "/images/fail.svg", // Иконка для ошибок


            timeout: 5000,
        });
    };

    const showSuccess = (message, title = 'Success') => {
        iziToast.success({
            ...commonOptions,

            message: message,
            iconUrl: "/images/success.svg", // Ваша иконка успеха


            timeout: 3000,
        });
    };

    const showWarning = (message, title = 'Warning') => {
        iziToast.warning({
            ...commonOptions,

            message: message,
            iconUrl: "/images/warning.svg", // Иконка предупреждения


            timeout: 4000,
        });
    };

    const showInfo = (message, title = 'Info') => {
        iziToast.info({
            ...commonOptions,
            title: title,
            message: message,
            iconUrl: "/images/info.svg", // Иконка информации


            timeout: 3000,
        });
    };

    return {
        showError,
        showSuccess,
        showWarning,
        showInfo,
    };
}