import { defineStore } from "pinia";
import axiosClient from "../api/axios";
import { useToast } from "@/composables/useToast.js";

const { showSuccess, showError } = useToast();

export const useUserStore = defineStore("user", {
    state: () => ({
        user: null,
        loading: false,
        errors: null,
        activeBill: null,
        bills:null,
    }),

    getters: {
        currentUser: (state) => state.user,
        isLoading: (state) => state.loading,
        isAuth: (state) => state.user !== null,
    },

    actions: {
        setUser(user) {
            this.user = user;
        },
        async fetchBills() {
            const response = await axiosClient.get("/assets/bills");
            this.bills = response.data.bills;
            return response;
        },

        clearUser() {
            this.user = null;
        },

        clearErrors() {
            this.errors = null;
        },
        async createBill(billData) {
            try {
                this.loading = true;
                const response = await axiosClient.post("/bills/create", billData);
                await this.fetchBills()
                showSuccess("Bill created successfully");
                return response;
            }
            catch (error) {
                this.errors = error.response?.data?.errors || { message: "Error creating bill" };
                showError(this.errors.message || "Error creating bill");
            }
            finally {
                this.loading = false;
            }
        },
        async fetchUser() {
            try {
                this.loading = true;
                const response = await axiosClient.get("/account/profile/get");
                this.setUser(response.data.user);
                return response;
            } catch (error) {
                throw error;
            } finally {
                this.loading = false;
            }
        },

        updateUserData(userData) {
            if (this.user) {
                this.user = { ...this.user, ...userData };
            }
        },

        async updatePassword($password_data) {
            try {
                this.loading = true;
                const response = await axiosClient.put(
                    "/account/password/update",
                    $password_data
                );
                this.loading = false;
                return response;
            } catch (error) {
                this.errors = error.response?.data?.errors || {
                    message: "Error update password",
                };
                return error.response.data.message;
            }
        },

        async toggle2FA(code) {
            try {
                const response = await axiosClient.post("/2fa/toggle", { code });
                showSuccess(response.data.message);
                return {
                    success: true,
                    is_2fa: response.data.is_2fa,
                };
            } catch (error) {
                this.errors = error.response?.data?.errors || { message: 'Error toggle 2FA' };
                showError(error.response?.data?.message || 'Error toggle 2FA');
                return {
                    success: false,
                    is_2fa: false,
                };
            }
        },


        async changePassword(password_data) {
            try {
                this.loading = true;
                const response = await axiosClient.post("/account/password/update", password_data);
                showSuccessToast(response.data.message);
                return response.data;
            } catch (error) {
                this.errors = error.response?.data?.errors || {
                    message: "Ошибка при изменении пароля",
                };
                renderErrorToast(error.response.data.message);
                return false;
            }
            finally {
                this.loading = false;
            }
        },
    },
});
