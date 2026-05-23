<template>
  <div class="container mx-auto p-4 md:p-6">
    <div class="bg-white shadow-lg rounded-lg p-6">
      <h1 class="text-3xl font-bold text-gray-800 mb-2">Collector Dashboard</h1>
      
      <div v-if="loading" class="text-center py-10">
        <p class="text-gray-500">Loading session status...</p>
      </div>

      <div v-else-if="error" class="text-center py-10 text-red-500 bg-red-50 rounded-lg p-4">
        <p><strong>Error:</strong> {{ error }}</p>
      </div>

      <div v-else>
        <!-- Session Active State -->
        <div v-if="session" class="space-y-6">
          <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-lg shadow">
            <div class="flex justify-between items-center">
              <div>
                <h2 class="font-bold text-lg">Session Active</h2>
                <p class="text-sm">Started at: {{ new Date(session.started_at).toLocaleString() }}</p>
              </div>
              <button @click="endSession" :disabled="isSubmitting" 
                      class="bg-yellow-500 hover:bg-yellow-600 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out disabled:bg-yellow-200 disabled:cursor-not-allowed">
                {{ isSubmitting ? 'Ending...' : 'End Session' }}
              </button>
            </div>
          </div>
          <RecordDonation @donation-recorded="handleDonationRecorded" />
        </div>

        <!-- No Session State -->
        <div v-else class="text-center py-10 bg-gray-50 rounded-lg">
          <h2 class="text-2xl font-semibold text-gray-700 mb-2">No Active Session</h2>
          <p class="text-gray-500 mb-4">You do not have an active session for today.</p>
          <button @click="startSession" :disabled="isSubmitting"
                  class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300 ease-in-out disabled:bg-indigo-300 disabled:cursor-not-allowed">
            {{ isSubmitting ? 'Starting...' : 'Start Session' }}
          </button>
        </div>
      </div>

      <!-- Donations Table -->
      <div class="mt-10">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-2xl font-bold text-gray-800">My Donations</h2>
          <button @click="exportDonations" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            Export to CSV
          </button>
        </div>
        <div class="bg-white shadow-md rounded-lg">
          <div class="table-wrapper">
          <table class="min-w-full leading-normal">
            <thead>
              <tr>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Donation ID</th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Session ID</th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Donor</th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="donations.data.length === 0">
                <td colspan="7" class="text-center py-10 text-gray-500">No donations recorded yet.</td>
              </tr>
              <tr v-for="donation in donations.data" :key="donation.id">
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.id }}</td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.collector_session_id }}</td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ formatDateTime(getDonationDateTime(donation)) }}</td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                  <p class="text-gray-900 whitespace-no-wrap">{{ donation.donor ? donation.donor.fullname : 'Not Found' }}</p>
                  <p class="text-gray-600 whitespace-no-wrap">{{ donation.donor_its_id }}</p>
                </td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.donation_type?.name || 'N/A' }}</td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ formatAmount(donation.amount) }} {{ donation.currency?.code || '' }}</td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                  <button
                    @click="shareReceiptViaWhatsApp(donation)"
                    :disabled="!canShareReceipt(donation)"
                    class="bg-green-500 hover:bg-green-700 text-white text-xs font-bold py-1 px-3 rounded disabled:bg-gray-300 disabled:cursor-not-allowed"
                  >
                    Share via WhatsApp
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          </div>
          <!-- Pagination -->
          <div v-if="donations.last_page > 1" class="px-5 py-5 bg-white border-t flex flex-col xs:flex-row items-center xs:justify-between">
            <div class="flex items-center">
              <button @click="fetchDonations(donations.current_page - 1)" :disabled="!donations.prev_page_url" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-l disabled:opacity-50">Prev</button>
              <span class="text-xs xs:text-sm text-gray-900 px-4">Page {{ donations.current_page }} of {{ donations.last_page }}</span>
              <button @click="fetchDonations(donations.current_page + 1)" :disabled="!donations.next_page_url" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-r disabled:opacity-50">Next</button>
            </div>
          </div>
        </div>

        <div v-if="currencySummary.length > 0" class="mt-4 bg-gray-50 border border-gray-200 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-gray-800 mb-3">Currency Summary (Current Page)</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div
              v-for="summary in currencySummary"
              :key="summary.code"
              class="bg-white border border-gray-200 rounded-md p-3"
            >
              <p class="text-sm text-gray-600">{{ summary.code }}</p>
              <p class="text-base font-bold text-gray-900">{{ formatAmount(summary.total) }}</p>
              <p class="text-xs text-gray-500">{{ summary.count }} donations</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { saveAs } from 'file-saver';
import RecordDonation from './RecordDonation.vue';

const session = ref(null);
const loading = ref(true);
const isSubmitting = ref(false);
const error = ref(null);
const donations = ref({ data: [] });

const fetchDonations = async (page = 1) => {
  try {
    const response = await window.axios.get(`/api/collector/donations?page=${page}`);
    donations.value = response.data;
  } catch (err) {
    console.error('Failed to fetch donations:', err);
  }
};

const checkSessionStatus = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await window.axios.get('/api/collector-sessions/status');
    session.value = response.data;
  } catch (err) {
    if (err.response && err.response.status === 404) {
      session.value = null;
    } else {
      error.value = 'Failed to fetch session status. Please try again.';
      console.error('Error fetching session status:', err);
    }
  } finally {
    loading.value = false;
  }
};

const startSession = async () => {
  isSubmitting.value = true;
  error.value = null;
  try {
    const response = await window.axios.post('/api/collector-sessions/start');
    session.value = response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to start session.';
    console.error('Error starting session:', err);
  } finally {
    isSubmitting.value = false;
  }
};

const endSession = async () => {
  isSubmitting.value = true;
  error.value = null;
  try {
    await window.axios.post('/api/collector-sessions/end');
    session.value = null; // Session is now closed
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to end session.';
    console.error('Error ending session:', err);
  } finally {
    isSubmitting.value = false;
  }
};

const handleDonationRecorded = (donation) => {
  // Optimistically show the new donation immediately, then sync with server.
  if (donation) {
    const existingDonations = donations.value?.data || [];
    donations.value = {
      ...donations.value,
      data: [donation, ...existingDonations.filter(item => item.id !== donation.id)],
    };
  }

  fetchDonations(1); // Refresh with authoritative data
};

const currencySummary = computed(() => {
  const totals = {};
  const donationList = donations.value?.data || [];

  donationList.forEach((donation) => {
    const code = donation?.currency?.code || 'N/A';
    const amount = Number(donation?.amount || 0);

    if (!totals[code]) {
      totals[code] = { code, total: 0, count: 0 };
    }

    totals[code].total += Number.isFinite(amount) ? amount : 0;
    totals[code].count += 1;
  });

  return Object.values(totals).sort((a, b) => a.code.localeCompare(b.code));
});

const exportDonations = async () => {
  try {
    const response = await window.axios.get('/api/collector/donations/export', {
      responseType: 'blob',
    });
    saveAs(new Blob([response.data]), 'my-donations.csv');
  } catch (err) {
    console.error('Failed to export donations:', err);
    error.value = 'Could not export donations. Please try again.';
  }
};

onMounted(() => {
  checkSessionStatus();
  fetchDonations();
});

const formatAmount = (amount) => {
  return new Intl.NumberFormat('en-US', { style: 'decimal', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
};

const formatDateTime = (dateTimeString) => {
  if (!dateTimeString) return 'N/A';
  return new Date(dateTimeString).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
};

const getDonationDateTime = (donation) => {
  return donation?.donated_at || donation?.date || donation?.created_at || null;
};

const getDonationPhone = (donation) => {
  return donation?.whatsapp_number || donation?.donor?.mobile || null;
};

const normalizeWhatsappPhone = (phone) => {
  if (!phone) return null;
  let normalized = String(phone).replace(/\D/g, '');
  if (normalized.startsWith('00')) {
    normalized = normalized.slice(2);
  } else if (normalized.startsWith('0')) {
    normalized = `94${normalized.slice(1)}`;
  }
  return normalized || null;
};

const getReceiptUrl = (donation) => {
  if (donation?.receipt_url) {
    return donation.receipt_url;
  }
  if (donation?.uuid) {
    return `${window.location.origin}/receipts/${donation.uuid}`;
  }
  return null;
};

const canShareReceipt = (donation) => {
  return !!(normalizeWhatsappPhone(getDonationPhone(donation)) && getReceiptUrl(donation));
};

const shareReceiptViaWhatsApp = (donation) => {
  const phone = normalizeWhatsappPhone(getDonationPhone(donation));
  const receiptUrl = getReceiptUrl(donation);

  if (!phone || !receiptUrl) {
    error.value = 'Cannot share receipt: missing donor phone or receipt link.';
    return;
  }

  const message = encodeURIComponent(`Donation receipt: ${receiptUrl}`);
  window.open(`https://wa.me/${phone}?text=${message}`, '_blank');
};
</script>

<style scoped>
/* Optional: Add any specific styles if needed, though Tailwind covers most cases. */

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
@media (max-width: 768px) {
  .table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .table-wrapper table {
    min-width: 800px;
  }
}
</style>
