<script setup>
import MainLayout from '@/layouts/MainLayout.vue';
import { router, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

function submit() {
    form.post('/register', {
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
                            <h2 class="h2_40 pb20">Create an account</h2>
                            <RouterLink
                                to="/Login"
                                class="link_15"
                                href="/login"
                                style="text-decoration: none"
                            >
                                Already have an account?
                                <RouterLink
                                    to="/login"
                                    class="btn btn_sign"
                                    style="background: var(--Blue, #c4e9fc)"
                                >
                                    Log in
                                </RouterLink>
                            </RouterLink>
                        </div>
                        <form class="login-block" @submit.prevent="submit">
                            <label class="form-item">
                                <input
                                    required
                                    :class="{
                                        input: true,
                                    }"
                                    type="email"
                                    name="email"
                                    v-model="form.email"
                                    placeholder="Email@email.com"
                                />
                            </label>
                            <label class="form-item">
                                <input
                                    required
                                    :class="{
                                        input: true,
                                    }"
                                    type="password"
                                    name="password"
                                    v-model="form.password"
                                    placeholder="Password"
                                    title="8 to 30 lowercase letters"
                                />
                            </label>
                            <label class="form-item">
                                <input
                                    required
                                    class=""
                                    type="password"
                                    name="password_confirmation"
                                    v-model="form.password_confirmation"
                                    placeholder="Confirm password"
                                    :class="{
                                        input: true,
                                    }"
                                />
                            </label>
                            <div class="form-check">
                                <input
                                    required
                                    type="checkbox"
                                    id="terms"
                                    name="terms"
                                    class="checkbox"
                                    v-model="form.terms"
                                />
                                <label
                                    for="terms"
                                    class="text_small_12 color-gray2"
                                    >I agree to
                                    <a
                                        href="#"
                                        style="
                                            color: inherit;
                                            text-decoration: underline;
                                            margin-left: 3px;
                                        "
                                    >
                                        terms of use
                                    </a>
                                </label>
                            </div>
                            <button
                                class="submit btn btn_16 color-white"
                                type="submit"
                            >
                                Sign up
                            </button>
                            <span class="text-red2 d-none">
                                {{ er }}
                            </span>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </MainLayout>
</template>

<style scoped></style>
