<script setup>
import MainLayout from '@/layots/MainLayout.vue';

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
              <RouterLink to="/register"
                          class="btn btn_sign"
                          style="background: var(--Blue, #c4e9fc)"
              >
                Register
              </RouterLink>
            </a>
          </div>
          <div class="login-block">
            <label class="form-item wrong">
              <input
                  required
                  class="input"
                  type="email"
                  name="email"
                  placeholder="Email@email.com"
              />

            </label>

            <label class="form-item">
              <input
                  class="input"
                  type="password"
                  name="password"
                  pattern="\w{8,30}"
                  placeholder="Password"
              />

            </label>
            <label  class="form-item">
              <input
                  class="input"
                  type="text"
                  pattern="\w{8,30}"
                  placeholder="Enter code from google authenticator"
              />

            </label>
            <div style="display: flex; justify-content: space-between">


              <div class="form-check">
                <input
                    type="checkbox"

                    id="remember"
                    name="remember"
                    class="checkbox"
                />
                <label for="remember" class="text_small_12 color-gray2"
                >Remember me</label
                >

              </div>
              <a href=""
                 class="link_15 color-gray2">
                Forgot password?
              </a>
            </div>
            <input
                class="submit btn btn_16 color-white"
                type="submit"
                value="Log in"
            />
          </div>
        </div>
      </div>
    </section>
  </main>
  </MainLayout>
</template>

<style scoped>

</style>