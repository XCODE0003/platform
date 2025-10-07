<script setup>
import Footer from '@/components/Base/Footer.vue';
import Header from '@/components/Base/Header.vue';
import { ModalsContainer } from 'vue-final-modal';
</script>

<template>
    <Header />
    <main>
        <slot />
    </main>
    <Footer />
    <ModalsContainer />
</template>
