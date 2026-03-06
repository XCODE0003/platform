<script setup>
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import { disabledButton, watchErrors, fieldNamesPresets } from '@/utils/system';
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
const page = usePage();
const { showSuccess, showError } = useToast();
const form = useForm({
    email: '',
    password: '',
    remember: false,
    code: '',
});
const errors = computed(() => page.props.errors);
watchErrors(errors, showError, fieldNamesPresets.auth);


function submit() {
    form.post('/login', {
        onSuccess: () => {
            showSuccess('Login successful!');
        },
        onError: () => {

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
                            <h2 class="h2_40 pb20">Login to your account</h2>
                            <Link
                                class="link_15"
                                href="/register"
                                style="text-decoration: none"
                            >
                                Don't have an account?
                                <button
                                    class="btn btn_sign"
                                    style="background: var(--Blue, #c4e9fc)"
                                >
                                    Register
                                </button>
                            </Link>
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
                                    :class="{
                                        'input-wrong': errors.email,
                                    }"
                                />
                            </label>

                            <label class="form-item">
                                <input
                                    class="input"
                                    type="password"
                                    name="password"
                                    v-model="form.password"
                                    placeholder="Password"
                                    :class="{
                                        'input-wrong': errors.password,
                                    }"
                                />
                            </label>
                            <label v-if="errors.code" class="form-item">
                                <input
                                    class="input"
                                    type="text"
                                    v-model="form.code"
                                    placeholder="Enter code from google authenticator"
                                    :class="{
                                        'input-wrong': errors.code,
                                    }"
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
                                        :class="{
                                            'input-wrong': errors.remember,
                                        }"
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
                                :disabled="form.processing || disabledButton(form.data(), ['email', 'password'])"
                            >
                                Log in
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </AuthLayout>
</template>

<style scoped></style>
