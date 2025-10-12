import { watch } from 'vue';
import { useToast } from '@/composables/useToast';
const { showInfo } = useToast();

const disabledButton = (formData, requiredFields = []) => {
    if (!formData) return true;

    // Если не указаны обязательные поля, проверяем все поля формы
    if (requiredFields.length === 0) {
        const excludeKeys = ['processing', 'errors', 'hasErrors', 'progress', 'recentlySuccessful', 'wasSuccessful'];
        requiredFields = Object.keys(formData).filter(key => !excludeKeys.includes(key));
    }

    return requiredFields.some(field => {
        const value = formData[field];
        if (typeof value === 'boolean') {
            return !value;
        }
        return value === '' || value === null || value === undefined;
    });
}

// Универсальная функция для отслеживания ошибок
const watchErrors = (errors, showError, fieldNames = {}) => {
    watch(errors, (newErrors) => {
        if (newErrors && Object.keys(newErrors).length > 0) {
            // Если есть общее сообщение об ошибке
            if (newErrors.message) {
                showError(newErrors.message, 'Error');
                return;
            }

            // Показываем ошибки для каждого поля
            Object.entries(newErrors).forEach(([field, message]) => {
                // Используем переданные имена полей или значение по умолчанию
                const fieldName = fieldNames[field] || field.charAt(0).toUpperCase() + field.slice(1);
                if (field === 'code') {
                    showInfo(message, '');
                    return;
                }
                showError(message, `${fieldName} Error`);
            });
        }
    }, { immediate: true });
}

// Предустановленные имена полей для разных форм
const fieldNamesPresets = {
    auth: {
        email: 'Email',
        password: 'Password',
        password_confirmation: 'Password Confirmation',
        terms: 'Terms',
        remember: 'Remember Me',
        code: 'Authentication Code'
    },
    profile: {
        name: 'Name',
        first_name: 'First Name',
        last_name: 'Last Name',
        phone: 'Phone Number',
        avatar: 'Avatar'
    },
    kyc: {
        sex: 'Sex',
        first_name: 'First Name',
        last_name: 'Last Name',
        phone: 'Phone Number',
        date_of_birth: 'Date of Birth',
        country: 'Country',
        city: 'City',
        address: 'Address',
        zip_code: 'Zip Code',
    },

};

const randomUUID = (id) => {
    // Простая хеш-функция для генерации детерминированного UUID от числа
    const hash = (str) => {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Преобразуем в 32-битное число
        }
        return Math.abs(hash);
    };

    const idStr = id.toString();
    const baseHash = hash(idStr);

    // Генерируем детерминированные части на основе хеша
    const part1 = (baseHash).toString(16).padStart(8, '0').slice(0, 8);
    const part2 = (hash(idStr + '1')).toString(16).slice(0, 4);
    const part3 = (hash(idStr + '2')).toString(16).slice(0, 4);
    const part4 = (hash(idStr + '3')).toString(16).slice(0, 4);
    const part5 = (hash(idStr + '4')).toString(16).padStart(12, '0').slice(0, 12);

    return `${part1}-${part2}-${part3}-${part4}-${part5}`;
}


export { disabledButton, watchErrors, fieldNamesPresets, randomUUID };