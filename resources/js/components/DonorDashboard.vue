<template>
  <div class="container mx-auto p-4 md:p-6">
    <div class="bg-white shadow-lg rounded-lg p-6">
      <h1 class="text-3xl font-bold text-gray-800 mb-4">Donor Dashboard</h1>
      <p class="text-gray-600 mb-6">Welcome! Here is a summary of your donations.</p>

      <div v-if="loading" class="text-center py-10">
        <p class="text-gray-500">Loading your donations...</p>
      </div>

      <div v-else-if="error" class="text-center py-10 text-red-500 bg-red-50 rounded-lg">
        <p>{{ error }}</p>
      </div>

      <div v-else-if="donations.length === 0" class="text-center py-10 text-gray-500 bg-gray-50 rounded-lg">
        <p>You have not made any donations yet.</p>
      </div>

      <div v-else>
        <div class="overflow-x-auto">
          <table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
              <tr>
                <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Date</th>
                <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Type</th>
                <th class="text-right py-3 px-4 uppercase font-semibold text-sm">Amount</th>
              </tr>
            </thead>
            <tbody class="text-gray-700">
              <tr v-for="donation in donations" :key="donation.id" class="border-b hover:bg-gray-100">
                <td class="text-left py-3 px-4">{{ formatDate(donation.donated_at) }}</td>
                <td class="text-left py-3 px-4">{{ donation.donation_type.name }}</td>
                <td class="text-right py-3 px-4 font-mono">{{ donation.currency.symbol }} {{ parseFloat(donation.amount).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const donations = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchDonations = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await window.axios.get('/api/donor/dashboard');
    donations.value = response.data;
  } catch (err) {
    console.error('Error fetching donations:', err);
    if (err.response && err.response.status === 401) {
        error.value = 'Authentication error. Please log in again.';
    } else {
        error.value = 'Failed to load donations. Please try again later.';
    }
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};

onMounted(fetchDonations);
</script>

<style scoped>
/* Scoped styles for the component */
</style>
