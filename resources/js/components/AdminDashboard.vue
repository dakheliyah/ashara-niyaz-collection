<template>
  <div class="dashboard-container">
    <h2>Admin Dashboard</h2>

    <!-- Event Selection Section -->
    <div class="event-selection-section">
      <h3>Select Event</h3>
      <div v-if="loadingEvents" class="loading">Loading events...</div>
      <div v-else-if="eventsError" class="error-message">{{ eventsError }}</div>
      <div v-else-if="events.length === 0" class="no-events">
        <p>No events found. Please create an event first.</p>
        <router-link to="/manage-events" class="btn btn-primary">Create Event</router-link>
      </div>
      <div v-else class="event-selector">
        <select v-model="selectedEventId" @change="onEventChange" class="form-control">
          <option value="">-- Select an Event --</option>
          <option v-for="event in events" :key="event.id" :value="event.id">
            {{ event.name }} ({{ formatDate(event.start_date) }} - {{ formatDate(event.end_date) }})
            <span v-if="event.is_active" class="active-badge">ACTIVE</span>
          </option>
        </select>
      </div>
    </div>

    <!-- Dashboard Content (only shown when event is selected) -->
    <div v-if="selectedEventId" class="dashboard-content">
      <div v-if="loading" class="loading">Loading dashboard data...</div>
      <div v-if="error" class="error-message">{{ error }}</div>

      <div v-if="dashboardData" class="event-dashboard">
        <!-- Event Info Header -->
        <div class="event-info-header">
          <h3>{{ selectedEvent.name }}</h3>
          <div class="event-details">
            <span class="event-dates">{{ formatDate(selectedEvent.start_date) }} - {{ formatDate(selectedEvent.end_date) }}</span>
            <span v-if="selectedEvent.is_active" class="status-badge active">ACTIVE</span>
            <span v-else class="status-badge inactive">INACTIVE</span>
          </div>
        </div>

        <!-- Key Metrics Section -->
        <div class="metrics-section">
          <h4>Key Metrics</h4>
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon">📊</div>
              <div class="stat-content">
                <h5>Total Sessions</h5>
                <p class="stat-number">{{ dashboardData.total_sessions }}</p>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">💰</div>
              <div class="stat-content">
                <h5>Total Donations</h5>
                <p class="stat-number">{{ dashboardData.total_donations }}</p>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">🏦</div>
              <div class="stat-content">
                <h5>Active Sessions</h5>
                <p class="stat-number">{{ activeSessions }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Collections Breakdown Section -->
        <div class="section-container">
          <h4>Collections Breakdown</h4>
          
          <!-- Currency Overview -->
          <div class="breakdown-subsection">
            <h5>💰 Total by Currency</h5>
            <div v-if="dashboardData.currency_totals && dashboardData.currency_totals.length > 0" class="currency-grid">
              <div v-for="currency in dashboardData.currency_totals" :key="currency.currency" class="currency-card">
                <div class="currency-symbol">{{ currency.currency_symbol }}</div>
                <div class="currency-details">
                  <div class="currency-code">{{ currency.currency }}</div>
                  <div class="currency-amount">{{ formatCurrencyAmount(currency.total_amount, currency.currency) }}</div>
                  <div class="currency-count">{{ currency.donation_count }} donations</div>
                </div>
              </div>
            </div>
            <div v-else class="no-data">No currency data available</div>
          </div>

          <!-- Category Breakdown -->
          <div class="breakdown-subsection">
            <h5>📋 Category-wise Breakdown</h5>
            <div v-if="dashboardData.category_breakdown && dashboardData.category_breakdown.length > 0" class="breakdown-table">
              <table>
                <thead>
                  <tr>
                    <th>Category</th>
                    <th>Currency</th>
                    <th>Total Amount</th>
                    <th>Donations</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="category in dashboardData.category_breakdown" :key="`${category.category}-${category.currency}`">
                    <td>{{ category.category }}</td>
                    <td>{{ category.currency_symbol }} {{ category.currency }}</td>
                    <td class="amount">{{ formatCurrencyAmount(category.total_amount, category.currency) }}</td>
                    <td>{{ category.donation_count }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="no-data">No category data available</div>
          </div>

          <!-- Collector Breakdown -->
          <div class="breakdown-subsection">
            <h5>👥 Collector-wise Breakdown</h5>
            <div v-if="dashboardData.collector_breakdown && dashboardData.collector_breakdown.length > 0" class="breakdown-table">
              <table>
                <thead>
                  <tr>
                    <th>Collector</th>
                    <th>ITS ID</th>
                    <th>Currency</th>
                    <th>Total Amount</th>
                    <th>Donations</th>
                    <th>Sessions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="collector in dashboardData.collector_breakdown" :key="`${collector.collector_its_id}-${collector.currency}`">
                    <td>{{ collector.collector_name }}</td>
                    <td>{{ collector.collector_its_id }}</td>
                    <td>{{ collector.currency_symbol }} {{ collector.currency }}</td>
                    <td class="amount">{{ formatCurrencyAmount(collector.total_amount, collector.currency) }}</td>
                    <td>{{ collector.donation_count }}</td>
                    <td>{{ collector.session_count }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="no-data">No collector data available</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Placeholder when no event is selected -->
    <div v-else-if="!loadingEvents && events.length > 0" class="no-event-selected">
      <div class="placeholder-content">
        <div class="placeholder-icon">📊</div>
        <h3>Select an Event</h3>
        <p>Choose an event from the dropdown above to view its dashboard and collection breakdown.</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminDashboard',
  data() {
    return {
      // Event selection
      events: [],
      selectedEventId: '',
      selectedEvent: null,
      loadingEvents: true,
      eventsError: null,
      
      // Dashboard data
      loading: false,
      error: null,
      dashboardData: null,
    };
  },
  computed: {
    activeSessions() {
      return this.dashboardData?.collector_sessions?.filter(session => !session.ended_at).length || 0;
    }
  },
  mounted() {
    this.fetchEvents();
  },
  methods: {
    async fetchEvents() {
      this.loadingEvents = true;
      this.eventsError = null;
      try {
        const response = await window.axios.get('/api/admin/events');
        this.events = response.data;
        
        // Auto-select active event if available
        const activeEvent = this.events.find(event => event.is_active);
        if (activeEvent) {
          this.selectedEventId = activeEvent.id;
          this.onEventChange();
        }
      } catch (error) {
        console.error('Error fetching events:', error);
        this.eventsError = 'Failed to load events.';
      } finally {
        this.loadingEvents = false;
      }
    },
    
    onEventChange() {
      if (this.selectedEventId) {
        this.selectedEvent = this.events.find(event => event.id == this.selectedEventId);
        this.fetchDashboardData();
      } else {
        this.selectedEvent = null;
        this.dashboardData = null;
      }
    },
    
    async fetchDashboardData() {
      if (!this.selectedEventId) return;
      
      this.loading = true;
      this.error = null;
      try {
        const response = await window.axios.get(`/api/admin/events/${this.selectedEventId}/dashboard`);
        this.dashboardData = response.data;
      } catch (error) {
        console.error('Error fetching dashboard data:', error);
        this.error = 'Failed to load dashboard data.';
      } finally {
        this.loading = false;
      }
    },
    
    formatCurrencyAmount(amount, currencyCode) {
      const numAmount = parseFloat(amount);
      if (isNaN(numAmount)) return amount;
      
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(numAmount);
    },
    
    formatDate(dateString) {
      if (!dateString) return '';
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    }
  },
};
</script>

<style scoped>
.dashboard-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.dashboard-container h2 {
  color: #2c3e50;
  margin-bottom: 2rem;
  text-align: center;
}

/* Event Selection Styles */
.event-selection-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border: 1px solid #e1e8ed;
}

.event-selection-section h3 {
  color: #2c3e50;
  margin-bottom: 1rem;
  font-size: 1.25rem;
}

.event-selector select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e1e8ed;
  border-radius: 8px;
  font-size: 1rem;
  background: white;
}

.event-selector select:focus {
  outline: none;
  border-color: #3498db;
}

.no-events {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
}

.btn {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-primary {
  background: #3498db;
  color: white;
}

.btn-primary:hover {
  background: #2980b9;
}

/* Event Info Header */
.event-info-header {
  background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
  color: white;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  text-align: center;
}

.event-info-header h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
}

.event-details {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.event-dates {
  font-size: 0.9rem;
  opacity: 0.9;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.active {
  background: #27ae60;
  color: white;
}

.status-badge.inactive {
  background: #95a5a6;
  color: white;
}

/* Dashboard Content */
.dashboard-content {
  animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Metrics Section */
.metrics-section {
  margin-bottom: 2rem;
}

.metrics-section h4 {
  color: #2c3e50;
  margin-bottom: 1rem;
  font-size: 1.25rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border: 1px solid #e1e8ed;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.stat-icon {
  font-size: 2rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8f9fa;
  border-radius: 50%;
}

.stat-content h5 {
  margin: 0 0 0.5rem 0;
  color: #7f8c8d;
  font-size: 0.9rem;
  font-weight: 500;
}

.stat-number {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 700;
  color: #2c3e50;
}

/* Section Container */
.section-container {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border: 1px solid #e1e8ed;
}

.section-container h4 {
  color: #2c3e50;
  margin-bottom: 1.5rem;
  font-size: 1.25rem;
}

.breakdown-subsection {
  margin-bottom: 2rem;
}

.breakdown-subsection:last-child {
  margin-bottom: 0;
}

.breakdown-subsection h5 {
  color: #34495e;
  margin-bottom: 1rem;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Currency Grid */
.currency-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.currency-card {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  border: 1px solid #e9ecef;
}

.currency-symbol {
  font-size: 1.5rem;
  font-weight: bold;
  color: #27ae60;
  width: 40px;
  text-align: center;
}

.currency-details {
  flex: 1;
}

.currency-code {
  font-size: 0.8rem;
  color: #7f8c8d;
  margin-bottom: 0.25rem;
}

.currency-amount {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.currency-count {
  font-size: 0.8rem;
  color: #95a5a6;
}

/* Tables */
.breakdown-table {
  overflow-x: auto;
}

.breakdown-table table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.breakdown-table th,
.breakdown-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e1e8ed;
}

.breakdown-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
}

.breakdown-table td.amount {
  font-weight: 600;
  color: #27ae60;
}

.breakdown-table tr:hover {
  background: #f8f9fa;
}

/* No Event Selected */
.no-event-selected {
  text-align: center;
  padding: 4rem 2rem;
  color: #7f8c8d;
}

.placeholder-content {
  max-width: 400px;
  margin: 0 auto;
}

.placeholder-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.placeholder-content h3 {
  color: #2c3e50;
  margin-bottom: 1rem;
}

.placeholder-content p {
  font-size: 1.1rem;
  line-height: 1.6;
}

/* Utility Classes */
.loading {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
  font-style: italic;
}

.error-message {
  background: #e74c3c;
  color: white;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  text-align: center;
}

.no-data {
  text-align: center;
  padding: 2rem;
  color: #95a5a6;
  font-style: italic;
}

/* Responsive Design */
@media (max-width: 768px) {
  .dashboard-container {
    padding: 1rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .currency-grid {
    grid-template-columns: 1fr;
  }
  
  .event-details {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .breakdown-table {
    font-size: 0.9rem;
  }
  
  .breakdown-table th,
  .breakdown-table td {
    padding: 0.5rem;
  }
}
</style>
