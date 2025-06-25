<template>
  <div id="app-layout">
    <header class="main-header">
      <h1>Ashara Niyaz Collection System</h1>
      <nav v-if="user">
        <div class="nav-links">
          <router-link to="/">Dashboard</router-link>
          <span v-if="user.permissions.can_view_sessions"> | <router-link to="/sessions">Sessions</router-link></span>
          <span v-if="user.permissions.can_create_events"> | <router-link to="/create-event">Create Event</router-link></span>
          <span v-if="user.permissions.can_manage_events"> | <router-link to="/manage-events">Manage Events</router-link></span>
          <span v-if="user.permissions.can_record_donations"> | <router-link to="/record-donation">Record Donation</router-link></span>
          <span v-if="user.permissions.can_manage_users"> | <router-link to="/user-management">User Management</router-link></span>
        </div>
        <div class="user-info">
          <div>Welcome, {{ user.role }} ({{ user.its_id }})</div>
          <div v-if="user.fullname" class="full-name">{{ user.fullname }}</div>
        </div>
      </nav>
      <nav v-else-if="loading">
        <span class="loading-message">Loading user information...</span>
      </nav>
      <nav v-else>
        <span class="error-message">Authentication required. Please check your token configuration.</span>
      </nav>
    </header>
    <main class="content">
      <router-view />
    </main>
  </div>
</template>

<script>
export default {
  name: 'App',
  data() {
    return {
      user: null,
      loading: true,
    };
  },
  async mounted() {
    await this.fetchUserInfo();
  },
  methods: {
    async fetchUserInfo() {
      try {
        console.log('Fetching user info...');
        console.log('Token being sent:', window.axios.defaults.headers.common['Token']);
        const response = await axios.get('/api/me');
        this.user = response.data;
        console.log('User loaded successfully:', this.user);
      } catch (error) {
        console.error('Failed to fetch user info:', error);
        console.error('Error response:', error.response?.data);
        this.user = null;
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style>
#app-layout {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
}

.main-header {
  background-color: #f8f9fa;
  padding: 20px;
  border-bottom: 1px solid #dee2e6;
}

nav {
  margin-top: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
}

nav .nav-links {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
}

nav a {
  color: #007bff;
  text-decoration: none;
  padding: 5px 10px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

nav a:hover {
  background-color: #e9ecef;
}

nav a.router-link-active {
  background-color: #007bff;
  color: white;
}

.user-info {
  color: #6c757d;
  font-weight: bold;
  font-style: italic;
  margin-left: 20px;
  text-align: right;
  line-height: 1.2;
}

.user-info .full-name {
  font-style: normal;
  font-size: 0.9em;
  color: #495057;
}

.loading-message {
  color: #6c757d;
  font-weight: bold;
  font-style: italic;
}

.error-message {
  color: #dc3545;
  font-weight: bold;
  font-style: italic;
}

.content {
  padding: 20px;
}
</style>
