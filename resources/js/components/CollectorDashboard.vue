<template>
  <div>
    <h1>Collector Dashboard</h1>
    <div v-if="isLoading">
      <p>Loading session status...</p>
    </div>
    <div v-else>
      <div v-if="error" class="error-message">
        <p>Error: {{ error }}</p>
      </div>
      <div v-if="session">
        <div class="session-info">
          <h2>Active Session</h2>
          <p><strong>Session started at:</strong> {{ new Date(session.started_at).toLocaleString() }}</p>
          <button @click="endSession" :disabled="isSubmitting" class="end-session-btn">
            {{ isSubmitting ? 'Ending...' : 'End Session' }}
          </button>
        </div>
        
        <!-- Record Donation Component when session is active -->
        <div class="donation-section">
          <RecordDonation />
        </div>
      </div>
      <div v-else>
        <h2>No Active Session</h2>
        <p>You do not have an active session for today.</p>
        <button @click="startSession" :disabled="isSubmitting">
          {{ isSubmitting ? 'Starting...' : 'Start Session' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import RecordDonation from './RecordDonation.vue';

export default {
  name: 'CollectorDashboard',
  components: {
    RecordDonation
  },
  data() {
    return {
      session: null,
      isLoading: true,
      isSubmitting: false,
      error: null,
    };
  },
  mounted() {
    this.checkSessionStatus();
  },
  methods: {
    async checkSessionStatus() {
      this.isLoading = true;
      this.error = null;
      try {
        const response = await window.axios.get('/api/collector-sessions/status');
        this.session = response.data;
      } catch (error) {
        if (error.response && error.response.status === 404) {
          this.session = null;
        } else {
          this.error = 'Failed to fetch session status.';
          console.error(error);
        }
      } finally {
        this.isLoading = false;
      }
    },
    async startSession() {
      this.isSubmitting = true;
      this.error = null;
      try {
        const response = await window.axios.post('/api/collector-sessions/start');
        this.session = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to start session.';
        console.error(error);
      } finally {
        this.isSubmitting = false;
      }
    },
    async endSession() {
      this.isSubmitting = true;
      this.error = null;
      try {
        await window.axios.post('/api/collector-sessions/end');
        this.session = null; // Session is now closed
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to end session.';
        console.error(error);
      } finally {
        this.isSubmitting = false;
      }
    },
  },
};
</script>

<style scoped>
.error-message {
  color: red;
  margin-bottom: 15px;
}

.session-info {
  background: linear-gradient(135deg, #2c3e50, #34495e);
  color: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 30px;
}

.session-info h2 {
  margin-top: 0;
  margin-bottom: 15px;
}

.end-session-btn {
  background-color: #e74c3c;
  color: white;
  border: none;
  margin-top: 15px;
}

.end-session-btn:hover:not(:disabled) {
  background-color: #c0392b;
}

.donation-section {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

button {
  padding: 10px 20px;
  font-size: 16px;
  cursor: pointer;
  border: 1px solid #ccc;
  border-radius: 5px;
  background-color: #f0f0f0;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
</style>
