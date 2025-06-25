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
        <h2>Active Session</h2>
        <p><strong>Session started at:</strong> {{ new Date(session.started_at).toLocaleString() }}</p>
        <button @click="endSession" :disabled="isSubmitting">
          {{ isSubmitting ? 'Ending...' : 'End Session' }}
        </button>
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
import axios from 'axios';

export default {
  name: 'CollectorDashboard',
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
        const response = await axios.get('/api/collector-sessions/status');
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
        const response = await axios.post('/api/collector-sessions/start');
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
        await axios.post('/api/collector-sessions/end');
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
