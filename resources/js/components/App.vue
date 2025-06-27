<template>
  <div id="app-layout">
    <header class="main-header">
      <div class="header-content">
        <h1>Ashara Niyaz Collection System</h1>
        <button @click="toggleMenu" class="hamburger-button" v-if="user">&#9776;</button>
      </div>
      <nav v-if="user" :class="{ 'is-open': isMenuOpen }">
        <div class="nav-links">
          <router-link :to="getDashboardRoute()" class="nav-link">{{ getDashboardLabel() }}</router-link>
          <router-link v-if="user.permissions && user.permissions.can_view_collector_dashboard_link" to="/collector" class="nav-link">Collector Dashboard</router-link>
          <router-link v-if="user.permissions && user.permissions.can_create_events" to="/create-event" class="nav-link">Create Event</router-link>
          <router-link v-if="user.permissions && user.permissions.can_manage_events" to="/manage-events" class="nav-link">Manage Events</router-link>
          <router-link v-if="user.permissions && user.permissions.can_manage_users" to="/admin/users" class="nav-link">User Management</router-link>
          <router-link v-if="user.permissions && user.permissions.can_view_collector_report" to="/admin/collector-report" class="nav-link">Collector Report</router-link>
          <div class="user-info">
            <div class="user-details">
              <div class="user-role">{{ getRoleLabel() }} ({{ user.its_id }})</div>
              <div v-if="user.fullname" class="full-name">{{ user.fullname }}</div>
            </div>
            <button @click="logout" class="logout-button">Logout</button>
          </div>
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
      isMenuOpen: false,
    };
  },
  async mounted() {
    await this.fetchUserInfo();
  },
  methods: {
    toggleMenu() {
      this.isMenuOpen = !this.isMenuOpen;
    },
    logout() {
      // Clear the cookie by setting its expiry to a past date
      document.cookie = "its_no=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
      // Redirect to the external login page
      window.location.href = 'https://colombo-relay.asharamubaraka.net/';
    },

    async fetchUserInfo() {
      try {
        console.log('Fetching user info...');
        console.log('Token being sent:', window.axios.defaults.headers.common['Token']);
        const response = await window.axios.get('/api/me');
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
    getDashboardRoute() {
      if (!this.user) return '/';
      
      // Route based on user role
      switch (this.user.role) {
        case 'admin':
          return '/admin';
        case 'collector':
          return '/collector';
        case 'donor':
          return '/donor';
        default:
          return '/';
      }
    },
    getDashboardLabel() {
      if (!this.user) return 'Dashboard';
      
      // Label based on user role
      switch (this.user.role) {
        case 'admin':
          return 'Admin Dashboard';
        case 'collector':
          return 'Collector Dashboard';
        case 'donor':
          return 'Donor Dashboard';
        default:
          return 'Dashboard';
      }
    },
    getRoleLabel() {
      if (!this.user) return 'User';
      
      // Capitalize and format role
      switch (this.user.role) {
        case 'admin':
          return 'Administrator';
        case 'collector':
          return 'Collector';
        case 'donor':
          return 'Donor';
        default:
          return this.user.role.charAt(0).toUpperCase() + this.user.role.slice(1);
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
  background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
  padding: 20px;
  border-bottom: 1px solid #dee2e6;
  color: white;
}

.main-header h1 {
  color: white;
  margin: 0 0 10px 0;
}

nav {
  margin-top: 10px;
  display: flex;
  align-items: center;
}

nav .nav-links {
  display: flex;
  gap: 20px;
  align-items: center;
  width: 100%;
}

nav .nav-link {
  color: white;
  text-decoration: none;
  padding: 8px 16px;
  border-radius: 4px;
  transition: background-color 0.3s ease;
  font-weight: 500;
}

nav .nav-link:hover {
  background-color: rgba(255, 255, 255, 0.1);
  text-decoration: none;
  color: white;
}

nav .nav-link.router-link-active {
  background-color: rgba(255, 255, 255, 0.2);
  font-weight: 600;
  color: white;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-left: auto;
}

.user-details {
  text-align: right;
}

.user-info .full-name {
  font-size: 0.9em;
  color: rgba(255, 255, 255, 0.8);
  font-style: italic;
}

.user-role {
  font-weight: bold;
  color: white;
}

.loading-message {
  color: rgba(255, 255, 255, 0.8);
  font-weight: bold;
}

.error-message {
  color: #ffcccb;
  font-weight: bold;
}

.content {
  padding: 20px;
}
.logout-button {
  background-color: #c0392b;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.3s ease;
  height: fit-content;
}

.logout-button:hover {
  background-color: #a93226;
}
.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.hamburger-button {
  display: none;
  background: none;
  border: none;
  color: white;
  font-size: 30px;
  cursor: pointer;
}

@media (max-width: 1024px) {
  .main-header {
    position: relative;
  }

  .hamburger-button {
    display: block;
  }

  .main-header nav {
    display: none;
  }

  .main-header nav.is-open {
    display: block;
    position: absolute;
    top: 85px; /* Adjust this value to match header height */
    left: 0;
    right: 0;
    background: #2c3e50;
    padding: 10px;
    z-index: 1000;
    border-top: 1px solid #34495e;
  }

  nav .nav-links {
    flex-direction: column;
    align-items: stretch;
    gap: 0;
  }

  nav .nav-link {
    text-align: left;
    padding: 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }
  
  .user-info {
    margin-left: 0;
    flex-direction: column;
    align-items: stretch;
    padding: 15px;
    gap: 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .user-details {
    text-align: left;
  }
}
</style>
