<template>
  <div class="collector-report-container">
    <h2 class="report-title">Collector Report</h2>
    <div class="report-form-card">
      <div class="form-group">
        <label for="event-select">Select Event:</label>
        <select id="event-select" v-model="selectedEventId" class="form-control">
          <option value="">All Events</option>
          <option v-for="event in events" :key="event.id" :value="event.id">
            {{ event.name }}
          </option>
        </select>
      </div>
      <div class="form-group">
        <label for="collector-its-id">Collector's ITS ID:</label>
        <input 
          type="text" 
          id="collector-its-id" 
          v-model="itsId"
          placeholder="Enter ITS ID"
          class="form-control"
        />
      </div>
      <button @click="getReport" :disabled="loading || !itsId" class="btn-submit">
        <span v-if="loading">Loading...</span>
        <span v-else>Generate Report</span>
      </button>
    </div>

    <div v-if="reportData" class="report-results-card">
      <h3 class="results-title">Report for ITS: {{ reportData.its_id }}</h3>
      <div v-if="reportData.collections.length">
        <ul class="currency-list">
          <li v-for="(item, index) in reportData.collections" :key="index" class="currency-item">
            <span class="currency-name">{{ item.currency_name }} ({{ item.currency_code }})</span>
            <span class="currency-total">{{ formatAmount(item.total_amount) }}</span>
          </li>
        </ul>
      </div>
      <div v-else class="no-data-message">
        No collections found for this ITS ID.
      </div>
    </div>
    
    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      itsId: '',
      loading: false,
      reportData: null,
      error: null,
      events: [],
      selectedEventId: '',
    };
  },
  mounted() {
    this.fetchEvents();
  },
  methods: {
    async fetchEvents() {
      try {
        const response = await window.axios.get('/api/admin/events');
        this.events = response.data;
      } catch (error) {
        console.error('Failed to fetch events:', error);
        this.error = 'Could not load events.';
      }
    },
    async getReport() {
      if (!this.itsId) return;
      this.loading = true;
      this.reportData = null;
      this.error = null;
      try {
        let url = `/api/admin/collector-report/${this.itsId}`;
        if (this.selectedEventId) {
          url += `?event_id=${this.selectedEventId}`;
        }
        const response = await window.axios.get(url);
        this.reportData = response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'An error occurred while fetching the report.';
      }
      this.loading = false;
    },
    formatAmount(amount) {
      return new Intl.NumberFormat().format(amount);
    },
  },
};
</script>

<style scoped>
.collector-report-container {
  max-width: 800px;
  margin: 2rem auto;
  padding: 2rem;
}
.report-title {
  text-align: center;
  margin-bottom: 2rem;
  color: #2c3e50;
}
.report-form-card, .report-results-card {
  background: #fff;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}
.form-group {
  margin-bottom: 1.5rem;
}
.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.btn-submit {
  width: 100%;
  padding: 0.75rem;
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s;
}
.btn-submit:disabled {
  background-color: #a9cce3;
  cursor: not-allowed;
}
.results-title {
  margin-bottom: 1.5rem;
}
.currency-list {
  list-style: none;
  padding: 0;
}
.currency-item {
  display: flex;
  justify-content: space-between;
  padding: 1rem;
  border-bottom: 1px solid #eee;
}
.currency-item:last-child {
  border-bottom: none;
}
.currency-name {
  font-weight: bold;
}
.error-message, .no-data-message {
  text-align: center;
  padding: 1rem;
  color: #e74c3c;
  background: #fbeae5;
  border-radius: 4px;
}
</style>
