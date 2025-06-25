<template>
  <div class="event-dashboard">
    <div class="dashboard-header">
      <h2>{{ event ? event.name : 'Event Dashboard' }}</h2>
      <div class="event-info" v-if="event">
        <span class="event-dates">{{ formatDate(event.start_date) }} - {{ formatDate(event.end_date) }}</span>
        <span class="event-status" :class="event.is_active ? 'active' : 'inactive'">
          {{ event.is_active ? 'Active' : 'Inactive' }}
        </span>
      </div>
    </div>

    <div v-if="loading" class="loading">Loading event dashboard...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    
    <div v-else class="dashboard-content">
      <!-- Key Metrics -->
      <div class="metrics-grid">
        <div class="metric-card">
          <h3>Total Sessions</h3>
          <div class="metric-value">{{ dashboardData.total_sessions || 0 }}</div>
        </div>
        <div class="metric-card">
          <h3>Total Donations</h3>
          <div class="metric-value">{{ dashboardData.total_donations || 0 }}</div>
        </div>
        <div class="metric-card">
          <h3>Active Sessions</h3>
          <div class="metric-value">{{ dashboardData.active_sessions || 0 }}</div>
        </div>
      </div>

      <!-- Currency Overview -->
      <div class="section">
        <h3>Total Collections by Currency</h3>
        <div class="currency-grid">
          <div v-for="currency in dashboardData.total_collected_by_currency" :key="currency.code" class="currency-card">
            <div class="currency-symbol">{{ currency.symbol }}</div>
            <div class="currency-amount">{{ formatCurrencyAmount(currency.total_amount, currency.code) }}</div>
            <div class="currency-code">{{ currency.code }}</div>
          </div>
        </div>
      </div>

      <!-- Collector Sessions with Reconciliation -->
      <div class="section">
        <h3>Collector Sessions</h3>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Collector</th>
                <th>ITS ID</th>
                <th>Status</th>
                <th>Started</th>
                <th>Ended</th>
                <th>Reconciliation</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="session in dashboardData.collector_sessions" :key="session.id">
                <td>{{ getCollectorName(session.its_id) }}</td>
                <td>{{ session.its_id }}</td>
                <td>
                  <span class="status-badge" :class="session.status">{{ session.status }}</span>
                </td>
                <td>{{ formatDateTime(session.started_at) }}</td>
                <td>{{ session.ended_at ? formatDateTime(session.ended_at) : '-' }}</td>
                <td>
                  <span class="reconciliation-badge" :class="session.reconciliation_status">
                    {{ session.reconciliation_status }}
                  </span>
                </td>
                <td class="actions">
                  <button 
                    v-if="session.status === 'ended' && session.reconciliation_status === 'pending'"
                    @click="reconcileSession(session.id)"
                    class="btn btn-primary btn-sm"
                    :disabled="reconcilingSession === session.id"
                  >
                    {{ reconcilingSession === session.id ? 'Reconciling...' : 'Reconcile' }}
                  </button>
                  <button 
                    @click="viewSessionBreakdown(session.id)"
                    class="btn btn-secondary btn-sm"
                  >
                    View Details
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Collector Breakdown -->
      <div class="section">
        <h3>Collector-wise Collections</h3>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Collector Name</th>
                <th>ITS ID</th>
                <th>Currency</th>
                <th>Total Amount</th>
                <th>Donations</th>
                <th>Sessions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in dashboardData.collector_breakdown" :key="`${item.collector_its_id}-${item.currency}`">
                <td class="collector-name">{{ item.collector_name || item.collector_its_id }}</td>
                <td class="its-id">{{ item.collector_its_id }}</td>
                <td class="currency-info">
                  <span class="currency-symbol">{{ item.currency_symbol }}</span>
                  {{ item.currency }}
                </td>
                <td class="amount highlight">{{ formatCurrencyAmount(item.total_amount, item.currency) }}</td>
                <td class="count">{{ item.donation_count }}</td>
                <td class="count">{{ item.session_count }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Category Breakdown -->
      <div class="section">
        <h3>Category-wise Collections</h3>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Category</th>
                <th>Currency</th>
                <th>Total Amount</th>
                <th>Donations</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in dashboardData.category_breakdown" :key="`${item.category}-${item.currency}`">
                <td class="category">{{ item.category }}</td>
                <td class="currency-info">
                  <span class="currency-symbol">{{ item.currency_symbol }}</span>
                  {{ item.currency }}
                </td>
                <td class="amount">{{ formatCurrencyAmount(item.total_amount, item.currency) }}</td>
                <td class="count">{{ item.donation_count }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Session Breakdown Modal -->
    <div v-if="showSessionModal" class="modal-overlay" @click="closeSessionModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Session Breakdown</h3>
          <button @click="closeSessionModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div v-if="sessionBreakdown">
            <div class="session-info">
              <h4>Session Details</h4>
              <p><strong>Collector:</strong> {{ getCollectorName(sessionBreakdown.session.its_id) }}</p>
              <p><strong>Started:</strong> {{ formatDateTime(sessionBreakdown.session.started_at) }}</p>
              <p><strong>Ended:</strong> {{ formatDateTime(sessionBreakdown.session.ended_at) }}</p>
              <p><strong>Total Donations:</strong> {{ sessionBreakdown.total_donations }}</p>
            </div>
            
            <div class="breakdown-tables">
              <h4>Currency Breakdown</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Currency</th>
                    <th>Amount</th>
                    <th>Count</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in sessionBreakdown.currency_breakdown" :key="item.code">
                    <td>{{ item.symbol }} {{ item.code }}</td>
                    <td>{{ formatCurrencyAmount(item.total_amount, item.code) }}</td>
                    <td>{{ item.donation_count }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EventDashboard',
  props: {
    eventId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      dashboardData: {},
      event: null,
      loading: true,
      error: null,
      reconcilingSession: null,
      showSessionModal: false,
      sessionBreakdown: null,
      collectorNames: {}
    };
  },
  async mounted() {
    await this.loadDashboard();
  },
  methods: {
    async loadDashboard() {
      try {
        this.loading = true;
        this.error = null;
        
        const response = await fetch(`/api/admin/events/${this.eventId}/dashboard`, {
          headers: {
            'Authorization': `Bearer ${window.authToken}`,
            'Token': window.authToken,
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        this.dashboardData = data;
        this.event = data.event;
        
        // Build collector names map
        if (data.collector_breakdown) {
          data.collector_breakdown.forEach(item => {
            if (item.collector_name) {
              this.collectorNames[item.collector_its_id] = item.collector_name;
            }
          });
        }
      } catch (error) {
        console.error('Error loading event dashboard:', error);
        this.error = 'Failed to load event dashboard data';
      } finally {
        this.loading = false;
      }
    },
    
    async reconcileSession(sessionId) {
      try {
        this.reconcilingSession = sessionId;
        
        const response = await fetch(`/api/admin/sessions/${sessionId}/reconcile`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${window.authToken}`,
            'Token': window.authToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        
        // Refresh dashboard data
        await this.loadDashboard();
        
        alert('Session reconciled successfully!');
      } catch (error) {
        console.error('Error reconciling session:', error);
        alert('Failed to reconcile session');
      } finally {
        this.reconcilingSession = null;
      }
    },
    
    async viewSessionBreakdown(sessionId) {
      try {
        const response = await fetch(`/api/admin/sessions/${sessionId}/breakdown`, {
          headers: {
            'Authorization': `Bearer ${window.authToken}`,
            'Token': window.authToken,
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        this.sessionBreakdown = await response.json();
        this.showSessionModal = true;
      } catch (error) {
        console.error('Error loading session breakdown:', error);
        alert('Failed to load session breakdown');
      }
    },
    
    closeSessionModal() {
      this.showSessionModal = false;
      this.sessionBreakdown = null;
    },
    
    getCollectorName(itsId) {
      return this.collectorNames[itsId] || itsId;
    },
    
    formatCurrencyAmount(amount, currency) {
      const num = parseFloat(amount);
      return num.toLocaleString('en-US', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
      });
    },
    
    formatDate(dateString) {
      if (!dateString) return '-';
      return new Date(dateString).toLocaleDateString();
    },
    
    formatDateTime(dateString) {
      if (!dateString) return '-';
      return new Date(dateString).toLocaleString();
    }
  }
};
</script>

<style scoped>
.event-dashboard {
  padding: 20px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 15px;
  border-bottom: 2px solid #e0e0e0;
}

.event-info {
  display: flex;
  gap: 15px;
  align-items: center;
}

.event-dates {
  color: #666;
  font-size: 14px;
}

.event-status {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: bold;
  text-transform: uppercase;
}

.event-status.active {
  background-color: #d4edda;
  color: #155724;
}

.event-status.inactive {
  background-color: #f8d7da;
  color: #721c24;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.metric-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  text-align: center;
}

.metric-card h3 {
  margin: 0 0 10px 0;
  color: #666;
  font-size: 14px;
  font-weight: normal;
}

.metric-value {
  font-size: 32px;
  font-weight: bold;
  color: #333;
}

.currency-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.currency-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  text-align: center;
}

.currency-symbol {
  font-size: 24px;
  font-weight: bold;
  color: #007bff;
  margin-bottom: 5px;
}

.currency-amount {
  font-size: 18px;
  font-weight: bold;
  color: #333;
  margin-bottom: 5px;
}

.currency-code {
  font-size: 12px;
  color: #666;
  text-transform: uppercase;
}

.section {
  background: white;
  margin-bottom: 30px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section h3 {
  margin: 0;
  padding: 20px 20px 0 20px;
  color: #333;
  font-size: 18px;
}

.table-container {
  padding: 20px;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
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

.status-badge,
.reconciliation-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: bold;
  text-transform: uppercase;
}

.status-badge.active {
  background-color: #d4edda;
  color: #155724;
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

.actions {
  display: flex;
  gap: 8px;
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

.btn-primary {
  background-color: #007bff;
  color: white;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn:hover {
  opacity: 0.8;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.highlight {
  font-weight: bold;
  color: #007bff;
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

.session-info {
  margin-bottom: 20px;
  padding: 15px;
  background-color: #f8f9fa;
  border-radius: 4px;
}

.breakdown-tables {
  margin-top: 20px;
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
</style>
