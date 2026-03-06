<script setup>
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import { computed } from 'vue';

const page = usePage();
const { showSuccess, showError } = useToast();

const props = defineProps({ status: String });

const form = useForm({ email: '' });

const statusMessage = computed(() => props.status);

function submit() {
    form.post('/forgot-password', {
        onSuccess: () => {
            showSuccess('Reset link sent! Check your inbox.');
            form.reset();
        },
        onError: (errors) => {
            Object.values(errors).forEach((msg) => showError(msg));
        },
    });
}
</script>

<template>
    <AuthLayout>
        <main class="login">
            <section class="login">
                <div class="container">
                    <div class="login-content">
                        <div class="login-title">
                            <h2 class="h2_40 pb20">Forgot password</h2>
                            <a href="/login" class="link_15" style="text-decoration: none">
                                Back to login
                            </a>
                        </div>

                        <p class="text_16 color-gray2 pb20">
                            Enter your email address and we'll send you a link to reset your password.
                        </p>

                        <div v-if="statusMessage" class="status-message pb20">
                            {{ statusMessage }}
                        </div>

                        <form class="login-block" @submit.prevent="submit">
                            <label class="form-item">
                                <input
                                    class="input"
                                    type="email"
                                    name="email"
                                    v-model="form.email"
                                    placeholder="Email@email.com"
                                    required
                                    :class="{ 'input-wrong': form.errors.email }"
                                />
                            </label>
                            <button
                                class="submit btn btn_16 color-white"
                                type="submit"
                                :disabled="form.processing || !form.email"
                            >
                                {{ form.processing ? 'Sending...' : 'Send reset link' }}
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </AuthLayout>
</template>

<style scoped>
.status-message {
    padding: 12px 16px;
    border-radius: 8px;
    background: rgba(121, 249, 149, 0.1);
    border: 1px solid rgba(121, 249, 149, 0.3);
    color: #79F995;
    font-size: 14px;
}
</style>
