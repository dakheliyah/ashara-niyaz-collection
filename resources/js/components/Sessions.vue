<template>
  <div class="dashboard-container">
    <h2>Session Management</h2>
    
    <!-- Admin Session Control -->
    <div class="session-control-panel">
      <h3>My Collection Session</h3>
      <div v-if="sessionLoading" class="loading-message">Checking session status...</div>
      <div v-else-if="sessionError" class="error-message">{{ sessionError }}</div>
      <div v-else>
        <div v-if="currentSession" class="active-session">
          <div class="session-info">
            <h4>✅ Active Session</h4>
            <p><strong>Started:</strong> {{ formatDateTime(currentSession.started_at) }}</p>
            <p><strong>Session ID:</strong> {{ currentSession.id }}</p>
          </div>
          <div class="session-actions">
            <button @click="endSession" :disabled="sessionSubmitting" class="action-button end-session-button">
              {{ sessionSubmitting ? 'Ending...' : 'End Session' }}
            </button>
            <router-link to="/record-donation" class="action-button record-donation-button">
              Record Donations
            </router-link>
          </div>
        </div>
        <div v-else class="no-session">
          <div class="session-info">
            <h4>No Active Session</h4>
            <p>Start a collection session to begin recording donations.</p>
          </div>
          <div class="session-actions">
            <button @click="startSession" :disabled="sessionSubmitting" class="action-button start-session-button">
              {{ sessionSubmitting ? 'Starting...' : 'Start Collection Session' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Sessions Overview -->
    <div class="sessions-overview">
      <h3>All Sessions</h3>
      <div v-if="loading" class="loading-message">Loading sessions...</div>
      <div v-if="error" class="error-message">{{ error }}</div>
      
      <div v-if="!loading && !error">
        <div v-if="sessions && sessions.length > 0">
          <div class="sessions-summary">
            <div class="summary-card">
              <h4>Total Sessions</h4>
              <p class="summary-number">{{ sessions.length }}</p>
            </div>
            <div class="summary-card">
              <h4>Active Sessions</h4>
              <p class="summary-number">{{ activeSessions }}</p>
            </div>
            <div class="summary-card">
              <h4>Total Collected</h4>
              <p class="summary-number">${{ totalCollected.toFixed(2) }}</p>
            </div>
          </div>

          <table class="user-table">
            <thead>
              <tr>
                <th>Session ID</th>
                <th>Collector (ITS ID)</th>
                <th>Event</th>
                <th>Session Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
                <th>Total Collected</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="session in sessions" :key="session.id">
                <td>{{ session.id }}</td>
                <td>{{ session.collector_name }}</td>
                <td>{{ session.event ? session.event.name : 'N/A' }}</td>
                <td>{{ formatDate(session.session_date) }}</td>
                <td>{{ session.formatted_start_time || 'N/A' }}</td>
                <td>
                  <span v-if="session.formatted_end_time">{{ session.formatted_end_time }}</span>
                  <span v-else class="status-active">Active</span>
                </td>
                <td>
                  <span :class="session.status === 'active' ? 'status-active' : 'status-inactive'">
                    {{ session.status }}
                  </span>
                </td>
                <td>${{ session.total_collected ? session.total_collected.toFixed(2) : '0.00' }}</td>
                <td>
                  <button @click="viewSessionDetails(session.id)" class="action-button view-button">
                    View Details
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-else class="no-sessions">
          <div class="empty-state">
            <h4>No Sessions Found</h4>
            <p>No collection sessions have been recorded yet. Start a session above to begin collecting donations.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Sessions',
  data() {
    return {
      loading: true,
      error: null,
      sessions: [],
      // Session management
      currentSession: null,
      sessionLoading: true,
      sessionError: null,
      sessionSubmitting: false,
    };
  },
  computed: {
    activeSessions() {
      return this.sessions.filter(session => session.status === 'active').length;
    },
    totalCollected() {
      return this.sessions.reduce((total, session) => total + (session.total_collected || 0), 0);
    }
  },
  mounted() {
    this.checkSessionStatus();
    this.fetchSessions();
  },
  methods: {
    async checkSessionStatus() {
      this.sessionLoading = true;
      this.sessionError = null;
      try {
        const response = await axios.get('/api/collector-sessions/status');
        this.currentSession = response.data;
      } catch (error) {
        if (error.response && error.response.status === 404) {
          this.currentSession = null;
        } else {
          this.sessionError = 'Failed to fetch session status.';
          console.error(error);
        }
      } finally {
        this.sessionLoading = false;
      }
    },
    async startSession() {
      this.sessionSubmitting = true;
      this.sessionError = null;
      try {
        const response = await axios.post('/api/collector-sessions/start');
        this.currentSession = response.data;
        // Refresh the sessions list to show the new session
        this.fetchSessions();
      } catch (error) {
        this.sessionError = error.response?.data?.message || 'Failed to start session.';
        console.error(error);
      } finally {
        this.sessionSubmitting = false;
      }
    },
    async endSession() {
      this.sessionSubmitting = true;
      this.sessionError = null;
      try {
        await axios.post('/api/collector-sessions/end');
        this.currentSession = null;
        // Refresh the sessions list to show the updated session
        this.fetchSessions();
      } catch (error) {
        this.sessionError = error.response?.data?.message || 'Failed to end session.';
        console.error(error);
      } finally {
        this.sessionSubmitting = false;
      }
    },
    fetchSessions() {
      this.loading = true;
      this.error = null;
      
      axios.get('/api/admin/sessions')
        .then(response => {
          this.sessions = response.data;
        })
        .catch(error => {
          console.error('Error fetching sessions:', error);
          this.error = 'Failed to load sessions. Please try again.';
        })
        .finally(() => {
          this.loading = false;
        });
    },
    viewSessionDetails(sessionId) {
      // Placeholder for viewing session details
      alert(`Viewing details for session ${sessionId}`);
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    },
    formatDateTime(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleString();
    }
  },
};
</script>

<style scoped>
.dashboard-container {
  padding: 2rem;
}

.session-control-panel {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border-left: 4px solid #007bff;
}

.session-control-panel h3 {
  margin-top: 0;
  color: #495057;
}

.active-session {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
  border-radius: 8px;
}

.no-session {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
  color: white;
  border-radius: 8px;
}

.session-info h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1.2rem;
}

.session-info p {
  margin: 0.25rem 0;
  opacity: 0.9;
}

.session-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.sessions-overview h3 {
  color: #495057;
  margin-bottom: 1rem;
}

.loading-message {
  text-align: center;
  padding: 2rem;
  font-size: 1.1rem;
  color: #6c757d;
}

.error-message {
  background-color: #f8d7da;
  color: #721c24;
  padding: 1rem;
  border-radius: 5px;
  margin-bottom: 1rem;
  border: 1px solid #f5c6cb;
}

.sessions-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.summary-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1.5rem;
  border-radius: 10px;
  text-align: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.summary-card h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1rem;
  font-weight: 500;
  opacity: 0.9;
}

.summary-number {
  margin: 0;
  font-size: 2rem;
  font-weight: bold;
}

.user-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.user-table th,
.user-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

.user-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: #495057;
}

.user-table tr:hover {
  background-color: #f8f9fa;
}

.status-active {
  color: #28a745;
  font-weight: 600;
}

.status-inactive {
  color: #6c757d;
}

.action-button {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
  text-decoration: none;
  display: inline-block;
  text-align: center;
}

.start-session-button {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
}

.start-session-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}

.end-session-button {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
}

.end-session-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
}

.record-donation-button {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  color: white;
}

.record-donation-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

.view-button {
  background-color: #007bff;
  color: white;
}

.view-button:hover {
  background-color: #0056b3;
}

.action-button:disabled {
  background-color: #aaa !important;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

.no-sessions {
  text-align: center;
  padding: 3rem;
}

.empty-state {
  max-width: 400px;
  margin: 0 auto;
}

.empty-state h4 {
  color: #6c757d;
  margin-bottom: 1rem;
}

.empty-state p {
  color: #868e96;
  line-height: 1.6;
}
</style>
