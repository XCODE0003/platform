<script setup>
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const { showError } = useToast();

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password', {
        onError: (errors) => {
            Object.values(errors).forEach((msg) => showError(msg));
            form.reset('password', 'password_confirmation');
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
                            <h2 class="h2_40 pb20">Reset password</h2>
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
                            <label class="form-item">
                                <input
                                    class="input"
                                    type="password"
                                    name="password"
                                    v-model="form.password"
                                    placeholder="New password"
                                    required
                                    :class="{ 'input-wrong': form.errors.password }"
                                />
                            </label>
                            <label class="form-item">
                                <input
                                    class="input"
                                    type="password"
                                    name="password_confirmation"
                                    v-model="form.password_confirmation"
                                    placeholder="Confirm new password"
                                    required
                                    :class="{ 'input-wrong': form.errors.password_confirmation }"
                                />
                            </label>
                            <button
                                class="submit btn btn_16 color-white"
                                type="submit"
                                :disabled="form.processing || !form.password || !form.password_confirmation"
                            >
                                {{ form.processing ? 'Saving...' : 'Set new password' }}
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </AuthLayout>
</template>

<style scoped></style>
