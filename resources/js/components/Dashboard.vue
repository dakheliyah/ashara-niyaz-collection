<template>
  <div class="dashboard-redirect">
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Redirecting to your dashboard...</p>
    </div>
    <div v-else-if="error" class="error-container">
      <h2>Access Error</h2>
      <p>{{ error }}</p>
      <button @click="retry" class="btn btn-primary">Retry</button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Dashboard',
  data() {
    return {
      loading: true,
      error: null
    };
  },
  async mounted() {
    await this.redirectToUserDashboard();
  },
  methods: {
    async redirectToUserDashboard() {
      try {
        this.loading = true;
        this.error = null;
        
        // Get user info from the parent App component or fetch it directly
        const response = await axios.get('/api/me');
        const user = response.data;
        
        // Redirect based on user role
        if (user.role === 'admin') {
          this.$router.replace('/admin');
        } else if (user.role === 'collector') {
          this.$router.replace('/collector');
        } else if (user.role === 'donor') {
          this.$router.replace('/donor');
        } else {
          throw new Error(`Unknown user role: ${user.role}`);
        }
      } catch (error) {
        console.error('Error redirecting to dashboard:', error);
        this.error = error.response?.data?.message || error.message || 'Failed to load user dashboard';
        this.loading = false;
      }
    },
    async retry() {
      await this.redirectToUserDashboard();
    }
  }
};
</script>

<style scoped>
.dashboard-redirect {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
  text-align: center;
}

.loading-container, .error-container {
  max-width: 400px;
  padding: 40px;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-container {
  color: #dc3545;
}

.error-container h2 {
  color: #dc3545;
  margin-bottom: 15px;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
  margin-top: 15px;
}

.btn-primary {
  background-color: #667eea;
  color: white;
}

.btn-primary:hover {
  background-color: #5a67d8;
}
</style>
