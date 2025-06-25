<template>
  <div class="dashboard-container">
    <h2>Create New Event</h2>
    <form @submit.prevent="createEvent" class="event-form">
      <div v-if="successMessage" class="success-message">{{ successMessage }}</div>
      <div v-if="errorMessage" class="error-message">{{ errorMessage }}</div>

      <div class="form-group">
        <label for="name">Event Name:</label>
        <input type="text" id="name" v-model="event.name" required>
      </div>

      <div class="form-group">
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" v-model="event.start_date" required>
      </div>

      <div class="form-group">
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" v-model="event.end_date" required>
      </div>

      <button type="submit" :disabled="loading">{{ loading ? 'Creating...' : 'Create Event' }}</button>
    </form>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'CreateEvent',
  data() {
    return {
      event: {
        name: '',
        start_date: '',
        end_date: '',
      },
      loading: false,
      successMessage: '',
      errorMessage: '',
    };
  },
  methods: {
    createEvent() {
      this.loading = true;
      this.successMessage = '';
      this.errorMessage = '';

      axios.post('/api/admin/events', this.event)
        .then(response => {
          this.successMessage = 'Event created successfully!';
          // Reset form
          this.event = { name: '', start_date: '', end_date: '' };
        })
        .catch(error => {
          console.error('Error creating event:', error);
          this.errorMessage = 'Failed to create event. Please check the details and try again.';
          if (error.response && error.response.data && error.response.data.errors) {
            this.errorMessage += ' ' + Object.values(error.response.data.errors).join(' ');
          }
        })
        .finally(() => {
          this.loading = false;
        });
    },
  },
};
</script>

<style scoped>
.dashboard-container {
  padding: 2rem;
}
.event-form {
  max-width: 500px;
  margin: 1rem auto;
  padding: 1.5rem;
  border: 1px solid #ccc;
  border-radius: 8px;
  background-color: #f9f9f9;
}
.form-group {
  margin-bottom: 1rem;
}
.form-group label {
  display: block;
  margin-bottom: 0.5rem;
}
.form-group input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 4px;
}
button {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  background-color: #007bff;
  color: white;
  cursor: pointer;
  font-size: 1rem;
}
button:disabled {
  background-color: #aaa;
}
.success-message {
  color: green;
  margin-bottom: 1rem;
}
.error-message {
  color: red;
  margin-bottom: 1rem;
}
</style>
