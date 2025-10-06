<script setup>
import MainLayout from '@/layots/MainLayout.vue';




</script>

<template>
  <MainLayout>
  <main class="login">
    <section class="login">
      <div class="container">
        <div v-if="!user.isAuth" class="login-content">
          <div class="login-title">
            <h2 class="h2_40 pb20">Create an account</h2>
            <RouterLink to="/Login"
                class="link_15"
                href="/login"
                style="text-decoration: none"
            >
              Already have an account?
              <RouterLink to="/login"
                          class="btn btn_sign"
                          style="background: var(--Blue, #c4e9fc)"
              >
                Log in
              </RouterLink>
            </RouterLink>
          </div>
          <div class="login-block">

            <label class="form-item wrong">
              <input
                  required
                  :class="{'input-wrong': errorsRef.email, 'input': true}"
                  type="email"
                  v-model="RegisterModel.email"
                  name="email"
                  placeholder="Email@email.com"
              />

            </label>
            <label class="form-item">
              <input
                  required
                  pattern="\w{8,30}"
                  :class="{'input-wrong': errorsRef.password, 'input': true}"
                  type="password"
                  v-model="RegisterModel.password"
                  name="password"
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
                  v-model="RegisterModel.password_confirmation"
                  placeholder="Confirm password"
                  :class="{'input-wrong': errorsRef.password_confirmation, 'input': true}"
              />

            </label>
            <div class="form-check">
              <input
                  required
                  type="checkbox"
                  id="terms"
                  v-model="RegisterModel.terms"
                  name="terms"
                  class="checkbox"

              />
              <label for="terms" class="text_small_12 color-gray2">I agree to
                <a href="#" style="color: inherit; text-decoration: underline; margin-left: 3px;">
                  terms of use
                </a>
              </label>
            </div>
            <input @click="register"
                   class="submit btn btn_16 color-white"
                   type="submit"
                   :value="loading ? 'Loading...' : 'Sign up'"
                   :disabled="!RegisterModel.email || !RegisterModel.password || !RegisterModel.password_confirmation || !RegisterModel.terms || loading"
            />
            <span v-for="er in errorsRef" class="text-red2 d-none">
              {{ er }}
            </span>
          </div>
        </div>

      </div>
    </section>
  </main>
  </MainLayout>
</template>

<style scoped>

</style>