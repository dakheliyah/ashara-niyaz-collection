<template>
  <div class="user-management">
    <div class="page-header">
      <h2>User Management</h2>
      <p>Manage system users, their status, and reconciliation requirements</p>
    </div>

    <!-- Create User Section -->
    <div class="create-user-section">
      <h2>Create New User</h2>
      <div class="create-user-form">
        <div class="form-row">
          <div class="form-group">
            <label for="its_id">ITS ID:</label>
            <input 
              type="number" 
              id="its_id"
              v-model="newUser.its_id" 
              class="form-control"
              placeholder="Enter 8-digit ITS ID"
              @input="fetchUserName"
              style="color: #000;"
            />
          </div>
          <div class="form-group">
            <label for="role">Role:</label>
            <select id="role" v-model="newUser.role" @change="onRoleChange" class="role-select">
              <option value="">Select Role</option>
              <option value="admin">Admin</option>
              <option value="collector">Collector</option>
            </select>
            <small v-if="newUser.role" style="color: #666; margin-top: 5px; display: block;">
              Selected: {{ newUser.role }}
            </small>
          </div>
          <div class="form-group">
            <button @click="createUser" class="btn btn-primary" :disabled="!canCreateUser">
              Create User
            </button>
          </div>
        </div>
        <div class="user-name-display" v-if="fetchedUserName">
          {{ fetchedUserName }}
        </div>
      </div>
    </div>

    <!-- Users List Section -->
    <div class="users-list-section">
      <h2>Existing Users</h2>
      
      <!-- Status Filter -->
      <div class="filters">
        <div class="filter-group">
          <label>Filter by Status:</label>
          <select v-model="statusFilter" @change="filterUsers">
            <option value="">All Users</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="archived">Archived</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Filter by Role:</label>
          <select v-model="roleFilter" @change="filterUsers">
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="collector">Collector</option>
          </select>
        </div>
      </div>

      <!-- Users Table -->
      <div class="section">
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>ITS ID</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Activity</th>
                <th>Unreconciled Sessions</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in filteredUsers" :key="user.id">
                <td class="user-name">{{ user.fullname || user.its_id }}</td>
                <td class="its-id">{{ user.its_id }}</td>
                <td class="role">
                  <span class="role-badge" :class="user.role.name">{{ user.role.label }}</span>
                </td>
                <td class="status">
                  <span class="status-badge" :class="user.status">{{ user.status }}</span>
                </td>
                <td class="last-activity">
                  {{ user.recent_session ? formatDateTime(user.recent_session.created_at) : 'Never' }}
                </td>
                <td class="unreconciled">
                  <span v-if="user.role.name === 'collector'" class="unreconciled-count" :class="{ 'has-unreconciled': user.unreconciled_sessions > 0 }">
                    {{ user.unreconciled_sessions || 0 }}
                  </span>
                  <span v-else>-</span>
                </td>
                <td class="actions">
                  <div class="action-buttons">
                    <button 
                      v-if="user.status === 'active'"
                      @click="deactivateUser(user.id)"
                      class="btn btn-warning btn-sm"
                      :disabled="user.role.name === 'collector' && user.unreconciled_sessions > 0"
                      :title="user.role.name === 'collector' && user.unreconciled_sessions > 0 ? 'Cannot deactivate collector with unreconciled sessions' : ''"
                    >
                      Deactivate
                    </button>
                    
                    <button 
                      v-if="user.status === 'inactive'"
                      @click="activateUser(user.id)"
                      class="btn btn-success btn-sm"
                    >
                      Activate
                    </button>
                    
                    <button 
                      v-if="user.status === 'inactive'"
                      @click="archiveUser(user.id)"
                      class="btn btn-secondary btn-sm"
                      :disabled="user.role.name === 'collector' && user.unreconciled_sessions > 0"
                      :title="user.role.name === 'collector' && user.unreconciled_sessions > 0 ? 'Cannot archive collector with unreconciled sessions' : ''"
                    >
                      Archive
                    </button>
                    
                    <button 
                      v-if="user.status === 'archived'"
                      @click="activateUser(user.id)"
                      class="btn btn-success btn-sm"
                    >
                      Reactivate
                    </button>
                    
                    <button 
                      v-if="user.role.name === 'collector' && user.recent_session"
                      @click="viewCollectorSessions(user.its_id)"
                      class="btn btn-info btn-sm"
                    >
                      View Sessions
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Statistics -->
    <div class="statistics">
      <div class="stat-card">
        <h4>Total Users</h4>
        <div class="stat-value">{{ users.length }}</div>
      </div>
      <div class="stat-card">
        <h4>Active Users</h4>
        <div class="stat-value">{{ users.filter(u => u.status === 'active').length }}</div>
      </div>
      <div class="stat-card">
        <h4>Inactive Users</h4>
        <div class="stat-value">{{ users.filter(u => u.status === 'inactive').length }}</div>
      </div>
      <div class="stat-card">
        <h4>Archived Users</h4>
        <div class="stat-value">{{ users.filter(u => u.status === 'archived').length }}</div>
      </div>
      <div class="stat-card">
        <h4>Unreconciled Sessions</h4>
        <div class="stat-value">{{ totalUnreconciledSessions }}</div>
      </div>
    </div>

    <!-- Collector Sessions Modal -->
    <div v-if="showSessionsModal" class="modal-overlay" @click="closeSessionsModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Collector Sessions - {{ selectedCollectorName }}</h3>
          <button @click="closeSessionsModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div v-if="collectorSessions.length === 0" class="no-sessions">
            No sessions found for this collector.
          </div>
          <div v-else>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Event</th>
                  <th>Started</th>
                  <th>Ended</th>
                  <th>Status</th>
                  <th>Reconciliation</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="session in collectorSessions" :key="session.id">
                  <td>{{ session.event ? session.event.name : 'Unknown Event' }}</td>
                  <td>{{ formatDateTime(session.started_at) }}</td>
                  <td>{{ session.ended_at ? formatDateTime(session.ended_at) : '-' }}</td>
                  <td>
                    <span class="status-badge" :class="session.status">{{ session.status }}</span>
                  </td>
                  <td>
                    <span class="reconciliation-badge" :class="session.reconciliation_status">
                      {{ session.reconciliation_status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'UserManagement',
  data() {
    return {
      users: [],
      filteredUsers: [],
      loading: true,
      error: null,
      statusFilter: '',
      roleFilter: '',
      showSessionsModal: false,
      collectorSessions: [],
      selectedCollectorName: '',
      newUser: {
        its_id: '',
        role: ''
      },
      fetchedUserName: '',
    };
  },
  computed: {
    totalUnreconciledSessions() {
      return this.users.reduce((total, user) => {
        return total + (user.unreconciled_sessions || 0);
      }, 0);
    },
    canCreateUser() {
      const isItsValid = String(this.newUser.its_id).length === 8;
      const isRoleSelected = this.newUser.role !== '';
      const isNameFound = this.fetchedUserName && this.fetchedUserName !== 'Name not found in database';
      return isItsValid && isRoleSelected && isNameFound;
    }
  },
  async mounted() {
    await this.loadUsers();
  },
  methods: {
    async loadUsers() {
      try {
        this.loading = true;
        this.error = null;
        
        const response = await window.axios.get('/api/admin/users');
        this.users = response.data.users;
        this.filterUsers();
      } catch (error) {
        console.error('Error loading users:', error);
        this.error = 'Failed to load users';
      } finally {
        this.loading = false;
      }
    },
    
    filterUsers() {
      this.filteredUsers = this.users.filter(user => {
        const statusMatch = !this.statusFilter || user.status === this.statusFilter;
        const roleMatch = !this.roleFilter || user.role.name === this.roleFilter;
        return statusMatch && roleMatch;
      });
    },
    
    async deactivateUser(userId) {
      if (!confirm('Are you sure you want to deactivate this user?')) {
        return;
      }
      
      try {
        await window.axios.put(`/api/admin/users/${userId}/deactivate`);
        await this.loadUsers();
        alert('User deactivated successfully');
      } catch (error) {
        console.error('Error deactivating user:', error);
        alert(error.response?.data?.message || 'Failed to deactivate user.');
      }
    },
    
    async activateUser(userId) {
      try {
        await window.axios.post(`/api/admin/users/${userId}/activate`);
        await this.loadUsers();
        alert('User activated successfully');
      } catch (error) {
        console.error('Error activating user:', error);
        alert('Failed to activate user');
      }
    },
    
    async archiveUser(userId) {
      if (!confirm('Are you sure you want to archive this user?')) {
        return;
      }
      
      try {
        await window.axios.post(`/api/admin/users/${userId}/archive`);
        await this.loadUsers();
        alert('User archived successfully');
      } catch (error) {
        console.error('Error archiving user:', error);
        alert(error.response?.data?.message || 'Failed to archive user.');
      }
    },
    
    async viewCollectorSessions(itsId) {
      try {
        const user = this.users.find(u => u.its_id === itsId);
        if (!user) return;

        this.selectedCollectorName = user.fullname || user.its_id;
        
        const response = await window.axios.get(`/api/admin/collectors/${itsId}/sessions`);
        this.collectorSessions = response.data.sessions;
        this.showSessionsModal = true;
      } catch (error) {
        console.error('Error loading collector sessions:', error);
        alert('Failed to load collector sessions');
      }
    },
    
    closeSessionsModal() {
      this.showSessionsModal = false;
      this.collectorSessions = [];
      this.selectedCollectorName = '';
    },
    
    async createUser() {
      if (!this.canCreateUser) return;
      try {
        const response = await window.axios.post('/api/admin/users', this.newUser);
        await this.loadUsers();
        
        const { token, fullname } = response.data;
        alert(`User created successfully!\n\nName: ${fullname}\nGenerated Token: ${token}\n\nPlease save this token securely and share it with the user. This is the only time it will be displayed.`);
        
        this.newUser = { its_id: '', role: '' };
        this.fetchedUserName = '';
      } catch (error) {
        console.error('Error creating user:', error);
        const errorMessage = error.response?.data?.message || 'Failed to create user';
        alert(`Error: ${errorMessage}`);
      }
    },
    
    async fetchUserName() {
      if (String(this.newUser.its_id).length === 8) {
        try {
          const response = await window.axios.get(`/api/admin/mumineen/${this.newUser.its_id}`);
          this.fetchedUserName = response.data?.fullname || 'Name not found in database';
        } catch (error) {
          this.fetchedUserName = 'Name not found in database';
        }
      } else {
        this.fetchedUserName = '';
      }
    },

    onRoleChange() {
      // This method is intentionally left blank as the logic is handled by the `canCreateUser` computed property.
    },

    formatDateTime(dateTimeString) {
      if (!dateTimeString) return 'N/A';
      const options = {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: 'numeric', minute: '2-digit', hour12: true,
      };
      try {
        return new Intl.DateTimeFormat('en-US', options).format(new Date(dateTimeString));
      } catch (e) {
        return dateTimeString;
      }
    },
  }
};
</script>

<style scoped>
.user-management {
  padding: 20px;
}

.page-header {
  margin-bottom: 30px;
  padding-bottom: 15px;
  border-bottom: 2px solid #e0e0e0;
}

.page-header h2 {
  margin: 0 0 10px 0;
  color: #333;
}

.page-header p {
  margin: 0;
  color: #666;
  font-size: 14px;
}

.filters {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  padding: 15px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.filter-group label {
  font-size: 12px;
  font-weight: bold;
  color: #666;
  text-transform: uppercase;
}

.filter-group select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.section {
  background: white;
  margin-bottom: 30px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table-container {
  padding: 20px;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

.data-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: #333;
}

.data-table tr:hover {
  background-color: #f8f9fa;
}

.role-badge,
.status-badge,
.reconciliation-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: bold;
  text-transform: uppercase;
}

.role-badge.admin {
  background-color: #e7f3ff;
  color: #0066cc;
}

.role-badge.collector {
  background-color: #fff2e7;
  color: #cc6600;
}

.status-badge.active {
  background-color: #d4edda;
  color: #155724;
}

.status-badge.inactive {
  background-color: #fff3cd;
  color: #856404;
}

.status-badge.archived {
  background-color: #f8d7da;
  color: #721c24;
}

.status-badge.ended {
  background-color: #d1ecf1;
  color: #0c5460;
}

.reconciliation-badge.pending {
  background-color: #fff3cd;
  color: #856404;
}

.reconciliation-badge.reconciled {
  background-color: #d4edda;
  color: #155724;
}

.unreconciled-count {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: bold;
  background-color: #f8f9fa;
  color: #666;
}

.unreconciled-count.has-unreconciled {
  background-color: #fff3cd;
  color: #856404;
}

.action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.btn {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
  text-decoration: none;
  display: inline-block;
}

.btn-warning {
  background-color: #ffc107;
  color: #212529;
}

.btn-success {
  background-color: #28a745;
  color: white;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-info {
  background-color: #17a2b8;
  color: white;
}

.btn:hover {
  opacity: 0.8;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.statistics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-top: 30px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  text-align: center;
}

.stat-card h4 {
  margin: 0 0 10px 0;
  color: #666;
  font-size: 14px;
  font-weight: normal;
}

.stat-value {
  font-size: 32px;
  font-weight: bold;
  color: #333;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  max-width: 800px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.modal-body {
  padding: 20px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

.no-sessions {
  text-align: center;
  padding: 40px;
  color: #666;
}

.loading,
.error {
  text-align: center;
  padding: 40px;
  color: #666;
}

.error {
  color: #dc3545;
}

.create-user-section {
  margin-bottom: 30px;
  padding: 20px;
  background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
  border-radius: 12px;
  color: white;
}

.create-user-section h2 {
  margin: 0 0 20px 0;
  color: white;
  font-size: 24px;
  font-weight: 600;
}

.create-user-form {
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.form-row {
  display: flex;
  gap: 20px;
  align-items: end;
  flex-wrap: wrap;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
  min-width: 200px;
}

.form-group label {
  font-size: 12px;
  font-weight: bold;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-control {
  padding: 12px 16px;
  border: 2px solid #e1e5e9;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.role-select {
  padding: 12px 16px;
  border: 2px solid #e1e5e9;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.3s ease;
  background-color: white;
  cursor: pointer;
  min-height: 44px;
}

.role-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-primary {
  background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
  border: none;
  padding: 12px 24px;
  border-radius: 6px;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(44, 62, 80, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.user-name-display {
  margin-top: 15px;
  padding: 12px 16px;
  background: #f8f9fa;
  border-radius: 6px;
  border-left: 4px solid #28a745;
  color: #495057;
  font-size: 14px;
}

.users-list-section {
  margin-top: 20px;
}

.users-list-section h2 {
  margin-bottom: 20px;
  color: #333;
  font-size: 20px;
  font-weight: 600;
}
</style>
