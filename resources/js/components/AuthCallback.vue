<template>
  <div class="auth-callback-container">
    <p>Authenticating, please wait...</p>
  </div>
</template>

<script>
export default {
  name: 'AuthCallback',
  mounted() {
    const token = this.$route.query.its_no;

    if (token) {
      // Set the cookie. Expires in 1 day.
      const d = new Date();
      d.setTime(d.getTime() + (24 * 60 * 60 * 1000));
      let expires = "expires=" + d.toUTCString();
      document.cookie = `its_no=${encodeURIComponent(token)};${expires};path=/`;
      
      // Immediately update the current axios instance so subsequent calls work
      if (window.axios) {
        window.axios.defaults.headers.common['Token'] = token;
      }

      // Use Vue router to redirect to avoid a full page reload
      this.$router.push('/');
    } else {
      // Handle error: no token found
      console.error('Authentication token missing in callback.');
      this.$router.push('/login'); // Or an error page
    }
  }
};
</script>

<style scoped>
.auth-callback-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  font-size: 1.2rem;
  color: #666;
}
</style>
