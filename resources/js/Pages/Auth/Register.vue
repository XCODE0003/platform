<script setup>
import MainLayout from '@/layouts/MainLayout.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';

import { computed, watch } from 'vue';
import { useToast } from '@/composables/useToast';
import { disabledButton, watchErrors, fieldNamesPresets } from '@/utils/system';
const page = usePage();
const { showError, showSuccess } = useToast();
const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});
const errors = computed(() => page.props.errors);
watchErrors(errors, showError, fieldNamesPresets.auth);

function submit() {
    form.post('/register', {
        onSuccess: () => {
            showSuccess('Account created successfully!', 'Welcome!');
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
                            <Link
                                href="/login"
                                class="link_15"
                                style="text-decoration: none"
                            >
                                Already have an account?
                                <Link
                                    href="/login"
                                    class="btn btn_sign"
                                    style="background: var(--Blue, #c4e9fc)"
                                >
                                    Log in
                                </Link>
                            </Link>
                        </div>
                        <form class="login-block" @submit.prevent="submit">
                            <div class="form-row">
                                <label class="form-item">
                                    <input
                                        required
                                        :class="{ input: true, 'input-wrong': errors.first_name }"
                                        type="text"
                                        name="first_name"
                                        v-model="form.first_name"
                                        placeholder="First name"
                                    />
                                </label>
                                <label class="form-item">
                                    <input
                                        required
                                        :class="{ input: true, 'input-wrong': errors.last_name }"
                                        type="text"
                                        name="last_name"
                                        v-model="form.last_name"
                                        placeholder="Last name"
                                    />
                                </label>
                            </div>
                            <label class="form-item">
                                <input
                                    required
                                    :class="{ input: true, 'input-wrong': errors.phone }"
                                    type="tel"
                                    name="phone"
                                    v-model="form.phone"
                                    placeholder="+1 234 567 8900"
                                />
                            </label>
                            <label class="form-item">
                                <input
                                    required
                                    :class="{
                                        input: true,
                                        'input-wrong': errors.email,
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
                                        'input-wrong': errors.password,
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
                                        'input-wrong': errors.password_confirmation,
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
                                    terms of use
                                </label>
                            </div>
                            <button
                                class="submit btn btn_16 color-white"
                                type="submit"
                                :disabled="form.processing || disabledButton(form.data(), ['first_name', 'last_name', 'phone', 'email', 'password', 'password_confirmation', 'terms'])"
                            >
                            {{ form.processing ? 'Creating...' : 'Sign up' }}
                            </button>
                            <span class="text-red2 d-none">
                                {{ errors }}
                            </span>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </MainLayout>
</template>

<style scoped>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
</style>
