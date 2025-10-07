<script setup>
import MainLayout from '@/layouts/MainLayout.vue';
import { router, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
    code: '',
});

function submit() {
    form.post('/login', {
        onSuccess: () => {
            router.visit('/');
        },
    });
}
</script>

<template>
    <MainLayout>
        <main class="login">
            <section class="login">
                <div class="container">
                    <div class="login-content">
                        <div class="login-title">
                            <h2 class="h2_40 pb20">Login to your account</h2>
                            <a
                                class="link_15"
                                href="/signup"
                                style="text-decoration: none"
                            >
                                Don't have an account?
                                <RouterLink
                                    to="/register"
                                    class="btn btn_sign"
                                    style="background: var(--Blue, #c4e9fc)"
                                >
                                    Register
                                </RouterLink>
                            </a>
                        </div>
                        <form class="login-block" @submit.prevent="submit">
                            <label class="form-item wrong">
                                <input
                                    required
                                    class="input"
                                    type="email"
                                    name="email"
                                    v-model="form.email"
                                    placeholder="Email@email.com"
                                />
                            </label>

                            <label class="form-item">
                                <input
                                    class="input"
                                    type="password"
                                    name="password"
                                    v-model="form.password"
                                    placeholder="Password"
                                />
                            </label>
                            <label class="form-item">
                                <input
                                    class="input"
                                    type="text"
                                    v-model="form.code"
                                    placeholder="Enter code from google authenticator"
                                />
                            </label>
                            <div
                                style="
                                    display: flex;
                                    justify-content: space-between;
                                "
                            >
                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        id="remember"
                                        name="remember"
                                        class="checkbox"
                                        v-model="form.remember"
                                    />
                                    <label
                                        for="remember"
                                        class="text_small_12 color-gray2"
                                        >Remember me</label
                                    >
                                </div>
                                <a
                                    href="/forgot-password"
                                    class="link_15 color-gray2"
                                >
                                    Forgot password?
                                </a>
                            </div>
                            <button
                                class="submit btn btn_16 color-white"
                                type="submit"
                            >
                                Log in
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </MainLayout>
</template>

<style scoped></style>
