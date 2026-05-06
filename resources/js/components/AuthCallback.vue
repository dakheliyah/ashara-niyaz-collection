<template>
  <div class="auth-callback-container">
    <p>Authenticating, please wait...</p>
  </div>
</template>

<script>
export default {
  name: 'AuthCallback',
  mounted() {
    const onlgnToken = this.$route.query.onlgn_token;
    if (onlgnToken) {
      const params = new URLSearchParams();
      params.set('onlgn_token', Array.isArray(onlgnToken) ? onlgnToken[0] : onlgnToken);
      const returnPath = this.$route.query.return_path;
      if (returnPath) {
        params.set('return_path', Array.isArray(returnPath) ? returnPath[0] : returnPath);
      }
      window.location.replace('/auth/onlgn-handoff?' + params.toString());
      return;
    }

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
      this.$router.push({ path: '/', query: { handoff_error: 'missing_token' } });
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
