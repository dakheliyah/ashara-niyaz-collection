<template>
  <div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Collector Reports</h1>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow mb-4 flex items-end space-x-4">
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
            <input type="date" id="start_date" v-model="filters.startDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
            <input type="date" id="end_date" v-model="filters.endDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="collector_its" class="block text-sm font-medium text-gray-700">Collector ITS</label>
            <input type="text" id="collector_its" v-model="filters.collectorIts" placeholder="Enter ITS ID" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="event_id" class="block text-sm font-medium text-gray-700">Event</label>
            <select id="event_id" v-model="filters.eventId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">All Events</option>
                <option v-for="event in events" :key="event.id" :value="event.id">{{ event.name }}</option>
            </select>
        </div>
        <div>
             <button @click="applyFilters" :disabled="loading" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:bg-blue-300">
                {{ loading ? 'Loading...' : 'Apply Filters' }}
            </button>
        </div>
    </div>

    <!-- Error Display -->
    <div v-if="error" class="my-4 p-4 bg-red-100 text-red-700 border border-red-400 rounded-lg">
        <p><strong>Error:</strong> {{ error }}</p>
    </div>

    <!-- Summary Report -->
    <div class="mb-8">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Collection Summary</h2>
        <button @click="exportSummary" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
          Export Summary
        </button>
      </div>
      <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full leading-normal">
          <thead>
            <tr>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Collector Name</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Collector ITS</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Session ID</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Event</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Session Start</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Donations</th>
              <th v-for="currency in currencies" :key="currency" class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                {{ currency }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="summaryReport.length === 0">
                <td :colspan="6 + currencies.length" class="text-center py-10 text-gray-500">
                    {{ loading ? 'Loading summary...' : 'No summary data available for the selected filters.' }}
                </td>
            </tr>
            <tr v-for="item in summaryReport" :key="item.session_id">
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ item.collector_name }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ item.collector_its }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ item.session_id }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ item.event_name }} ({{ item.event_id }})</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ formatDate(item.session_start) }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ item.total_donations }}</td>
              <td v-for="currency in currencies" :key="currency" class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                {{ formatAmount(item[currency] || 0) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Detailed Report -->
    <div>
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Detailed Donations Report</h2>
        <button @click="exportDetailed" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
          Export Detailed
        </button>
      </div>
      <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full leading-normal">
          <thead>
            <tr>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Donation ID</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Collector</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Session ID</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Event</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Donor ITS</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Donor Name</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Reconciliation</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="detailedReport.data.length === 0">
                <td colspan="10" class="text-center py-10 text-gray-500">
                    {{ loading ? 'Loading details...' : 'No detailed donations available for the selected filters.' }}
                </td>
            </tr>
            <tr v-for="donation in detailedReport.data" :key="donation.id">
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.id }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.collector_session.collector.fullname }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.collector_session_id }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.collector_session.event ? `${donation.collector_session.event.name} (${donation.collector_session.event.id})` : 'N/A' }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ formatDate(donation.donated_at) }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.donor_its_id }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.donor ? donation.donor.fullname : 'Not Found' }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.donation_type.name }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ formatAmount(donation.amount) }} {{ donation.currency.code }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                <div class="flex flex-col">
                    <span :class="reconciliationStatusClass(donation.reconciliation_status)" class="px-2 py-1 text-xs font-semibold leading-tight rounded-full">
                        {{ donation.reconciliation_status || 'Unreconciled' }}
                    </span>
                    <span class="text-xs text-gray-600 mt-1">
                        {{ formatAmount(donation.reconciled_amount || 0) }} / {{ formatAmount(donation.amount) }}
                    </span>
                    <button v-if="donation.reconciliation_status !== 'fully_reconciled'" @click="openReconciliationModal(donation)" class="mt-2 text-xs bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-1 px-2 rounded">
                        Reconcile
                    </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div class="px-5 py-5 bg-white border-t flex flex-col xs:flex-row items-center xs:justify-between">
        <div class="inline-flex mt-2 xs:mt-0">
          <button @click="fetchDetailedReport(detailedReport.prev_page_url)" :disabled="!detailedReport.prev_page_url" class="text-sm bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-l disabled:opacity-50">
            Prev
          </button>
          <button @click="fetchDetailedReport(detailedReport.next_page_url)" :disabled="!detailedReport.next_page_url" class="text-sm bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-r disabled:opacity-50">
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Reconciliation Modal -->
    <div v-if="showReconciliationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
      <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
          <h3 class="text-lg leading-6 font-medium text-gray-900">Reconcile Donation #{{ currentDonation.id }}</h3>
          <div class="mt-2 px-7 py-3">
            <p class="text-sm text-gray-500 mb-2">
                Total Amount: {{ formatAmount(currentDonation.amount) }} {{ currentDonation.currency.code }}
            </p>
            <p class="text-sm text-gray-500 mb-4">
                Remaining: {{ formatAmount(currentDonation.amount - (currentDonation.reconciled_amount || 0)) }} {{ currentDonation.currency.code }}
            </p>
            <input type="number" v-model.number="reconciliationAmount" placeholder="Enter amount to reconcile" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <div v-if="reconciliationError" class="mt-2 text-sm text-red-600">
              {{ reconciliationError }}
            </div>
          </div>
          <div class="items-center px-4 py-3">
            <button @click="submitReconciliation" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300">
              Submit Reconciliation
            </button>
            <button @click="closeReconciliationModal" class="mt-2 px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { saveAs } from 'file-saver';

export default {
  data() {
    return {
      summaryReport: [],
      events: [],
      currencies: [],
      detailedReport: {
        data: [],
      },
      filters: {
        startDate: '',
        endDate: '',
        collectorIts: '',
        eventId: '',
      },
      loading: false,
      error: null,
      showReconciliationModal: false,
      currentDonation: null,
      reconciliationAmount: null,
      reconciliationError: null,
    };
  },
  created() {
    this.fetchEvents();
    this.applyFilters();
  },
  methods: {
    applyFilters() {
      this.fetchSummaryReport();
      this.fetchDetailedReport('/api/admin/reports/detailed');
    },
    async fetchEvents() {
        try {
            const response = await window.axios.get('/api/admin/events');
            this.events = response.data;
        } catch (error) {
            console.error('Error fetching events:', error);
            this.error = 'Failed to load events for filtering.';
        }
    },
    async fetchSummaryReport() {
      this.loading = true;
      this.error = null;
      try {
        const params = {
            start_date: this.filters.startDate,
            end_date: this.filters.endDate,
            collector_its: this.filters.collectorIts,
            event_id: this.filters.eventId,
        };
        const response = await window.axios.get('/api/admin/reports/summary', { params });
        this.summaryReport = response.data.summary;
        this.currencies = response.data.currencies;
      } catch (error) {
        console.error('Error fetching summary report:', error);
        this.error = 'Failed to load summary report. Please try again.';
      } finally {
        this.loading = false;
      }
    },
    async fetchDetailedReport(url) {
      if (!url) return;
      this.loading = true;
      this.error = null;
      try {
        const urlObject = new URL(url, window.location.origin);
        if (this.filters.startDate) {
            urlObject.searchParams.set('start_date', this.filters.startDate);
        }
        if (this.filters.endDate) {
            urlObject.searchParams.set('end_date', this.filters.endDate);
        }
        if (this.filters.collectorIts) {
            urlObject.searchParams.set('collector_its', this.filters.collectorIts);
        }
        if (this.filters.eventId) {
            urlObject.searchParams.set('event_id', this.filters.eventId);
        }
        const response = await window.axios.get(urlObject.pathname + urlObject.search);
        this.detailedReport = response.data;
      } catch (error) {
        console.error('Error fetching detailed report:', error);
        this.error = 'Failed to load detailed report. Please try again.';
      } finally {
        this.loading = false;
      }
    },
    async exportSummary() {
        try {
            const params = {
                start_date: this.filters.startDate,
                end_date: this.filters.endDate,
                collector_its: this.filters.collectorIts,
            };
            const response = await window.axios.get('/api/admin/reports/summary/export', {
                responseType: 'blob', // Important
                params
            });
            saveAs(new Blob([response.data]), 'collector-summary-report.csv');
        } catch (error) {
            console.error('Error exporting summary report:', error);
            this.error = 'Failed to export summary report.';
        }
    },
    async exportDetailed() {
        try {
            const params = {
                start_date: this.filters.startDate,
                end_date: this.filters.endDate,
                collector_its: this.filters.collectorIts,
            };
            const response = await window.axios.get('/api/admin/reports/detailed/export', {
                responseType: 'blob', // Important
                params
            });
            saveAs(new Blob([response.data]), 'collector-detailed-report.csv');
        } catch (error) {
            console.error('Error exporting detailed report:', error);
            this.error = 'Failed to export detailed report.';
        }
    },
    formatDate(dateString) {
      if (!dateString) return '';
      const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      return new Date(dateString).toLocaleDateString(undefined, options);
    },
    formatAmount(amount) {
        const number = parseFloat(amount);
        if (isNaN(number)) {
            return '0.00';
        }
        return number.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    },
    openReconciliationModal(donation) {
      this.currentDonation = donation;
      this.reconciliationAmount = null;
      this.reconciliationError = null;
      this.showReconciliationModal = true;
    },
    closeReconciliationModal() {
      this.showReconciliationModal = false;
      this.currentDonation = null;
    },
    async submitReconciliation() {
      if (!this.reconciliationAmount || this.reconciliationAmount <= 0) {
        this.reconciliationError = 'Please enter a valid amount.';
        return;
      }

      this.reconciliationError = null;
      try {
        const response = await window.axios.put(`/api/admin/donations/${this.currentDonation.id}/reconcile`, {
          reconciled_amount: this.reconciliationAmount,
        });

        // Update the donation in the local detailedReport data
        const index = this.detailedReport.data.findIndex(d => d.id === this.currentDonation.id);
        if (index !== -1) {
          this.detailedReport.data.splice(index, 1, response.data);
        }

        this.closeReconciliationModal();
      } catch (error) {
        if (error.response && error.response.data && error.response.data.reconciled_amount) {
          this.reconciliationError = error.response.data.reconciled_amount[0];
        } else {
          this.reconciliationError = 'An unexpected error occurred.';
        }
        console.error('Error submitting reconciliation:', error);
      }
    },
    reconciliationStatusClass(status) {
        switch (status) {
            case 'fully_reconciled':
                return 'bg-green-200 text-green-800';
            case 'partially_reconciled':
                return 'bg-yellow-200 text-yellow-800';
            default:
                return 'bg-red-200 text-red-800';
        }
    }
  },
};
</script>
