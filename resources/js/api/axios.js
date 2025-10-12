import axios from 'axios';

const axiosClient = axios.create({
    baseURL: '/',
    
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    
    timeout: 10000,
});
axiosClient.interceptors.request.use(
    (config) => {
     
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

axiosClient.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        if (error.response) {
    
            
            if (error.response.status === 404) {
                console.error('Ресурс не найден');
            }
        }
        
        return Promise.reject(error);
    }
);

export default axiosClient;
