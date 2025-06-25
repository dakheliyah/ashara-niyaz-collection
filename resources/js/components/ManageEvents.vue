<template>
  <div class="dashboard-container">
    <h2>Manage Events</h2>
    <div v-if="loading">Loading events...</div>
    <div v-if="errorMessage" class="error-message">{{ errorMessage }}</div>
    <div v-if="successMessage" class="success-message">{{ successMessage }}</div>
    
    <table v-if="events.length" class="user-table">
      <thead>
        <tr>
          <th>Event Name</th>
          <th>Start Date</th>
          <th>End Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="event in events" :key="event.id">
          <td>{{ event.name }}</td>
          <td>{{ event.start_date }}</td>
          <td>{{ event.end_date }}</td>
          <td>
            <span :class="event.is_active ? 'status-active' : 'status-inactive'">
              {{ event.is_active ? 'Active' : 'Inactive' }}
            </span>
          </td>
          <td>
            <button @click="setActive(event)" :disabled="event.is_active || actionLoading" class="action-button activate-button">
              Set Active
            </button>
            <button @click="deactivate(event)" :disabled="!event.is_active || actionLoading" class="action-button deactivate-button">
              Deactivate
            </button>
            <button @click="editEvent(event)" class="action-button edit-button" :disabled="actionLoading">
              Edit
            </button>
          </td>
        </tr>
      </tbody>
    </table>
    <div v-else-if="!loading">
      <p>No events found. You can create one from the 'Create Event' page.</p>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ManageEvents',
  data() {
    return {
      events: [],
      loading: true,
      actionLoading: false,
      errorMessage: '',
      successMessage: '',
    };
  },
  created() {
    this.fetchEvents();
  },
  methods: {
    fetchEvents() {
      this.loading = true;
      axios.get('/api/admin/events')
        .then(response => {
          this.events = response.data;
        })
        .catch(error => {
          this.errorMessage = 'Failed to load events.';
          console.error('Error fetching events:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    setActive(eventToActivate) {
      this.actionLoading = true;
      this.errorMessage = '';
      this.successMessage = '';
      axios.put(`/api/admin/events/${eventToActivate.id}/set-active`)
        .then(response => {
          this.successMessage = `Event '${eventToActivate.name}' is now active.`;
          this.fetchEvents(); // Refresh the list
        })
        .catch(error => {
          this.errorMessage = 'Failed to set event as active.';
          console.error('Error setting active event:', error);
        })
        .finally(() => {
          this.actionLoading = false;
        });
    },
    deactivate(eventToDeactivate) {
      this.actionLoading = true;
      this.errorMessage = '';
      this.successMessage = '';
      axios.put(`/api/admin/events/${eventToDeactivate.id}/deactivate`)
        .then(response => {
          this.successMessage = `Event '${eventToDeactivate.name}' is now inactive.`;
          this.fetchEvents(); // Refresh the list
        })
        .catch(error => {
          this.errorMessage = 'Failed to deactivate event.';
          console.error('Error deactivating event:', error);
        })
        .finally(() => {
          this.actionLoading = false;
        });
    },
    editEvent(event) {
      // For now, this is a placeholder.
      // We can implement a modal or a separate edit page later.
      alert(`Editing event: ${event.name}`);
    },
  },
};
</script>

<style scoped>
.dashboard-container {
  padding: 2rem;
}
.user-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}
.user-table th, .user-table td {
  border: 1px solid #dee2e6;
  padding: 0.75rem;
  text-align: left;
}
.user-table th {
  background-color: #e9ecef;
}
.status-active {
  color: green;
  font-weight: bold;
}
.status-inactive {
  color: #6c757d;
}
.action-button {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-right: 5px;
}
.action-button:disabled {
  background-color: #aaa;
  cursor: not-allowed;
}
.activate-button {
  background-color: #007bff;
  color: white;
}
.deactivate-button {
  background-color: #dc3545;
  color: white;
}
.edit-button {
  background-color: #ffc107;
}
.success-message, .error-message {
  margin-bottom: 1rem;
  padding: 1rem;
  border-radius: 5px;
}
.success-message {
  background-color: #d4edda;
  color: #155724;
}
.error-message {
  background-color: #f8d7da;
  color: #721c24;
}
</style>
