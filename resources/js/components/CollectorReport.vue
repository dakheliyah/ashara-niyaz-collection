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
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Sessions</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Donations</th>
              <th v-for="currency in currencies" :key="currency" class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                {{ currency }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="summaryReport.length === 0">
                <td :colspan="4 + currencies.length" class="text-center py-10 text-gray-500">
                    {{ loading ? 'Loading summary...' : 'No summary data available for the selected filters.' }}
                </td>
            </tr>
            <tr v-for="item in summaryReport" :key="item.collector_its">
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ item.collector_name }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ item.collector_its }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ item.total_sessions }}</td>
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
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Donor ITS</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Donor Name</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
              <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="detailedReport.data.length === 0">
                <td colspan="8" class="text-center py-10 text-gray-500">
                    {{ loading ? 'Loading details...' : 'No detailed donations available for the selected filters.' }}
                </td>
            </tr>
            <tr v-for="donation in detailedReport.data" :key="donation.id">
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.id }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.collector_session.collector.fullname }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.collector_session_id }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ formatDate(donation.donated_at) }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.donor_its_id }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.donor.fullname }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ donation.donation_type.name }}</td>
              <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ formatAmount(donation.amount) }} {{ donation.currency.code }}</td>
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
  </div>
</template>

<script>
import { saveAs } from 'file-saver';

export default {
  data() {
    return {
      summaryReport: [],
      currencies: [],
      detailedReport: {
        data: [],
      },
      filters: {
        startDate: '',
        endDate: '',
        collectorIts: '',
      },
      loading: false,
      error: null,
    };
  },
  created() {
    this.applyFilters();
  },
  methods: {
    applyFilters() {
      this.fetchSummaryReport();
      this.fetchDetailedReport('/api/admin/reports/detailed');
    },
    async fetchSummaryReport() {
      this.loading = true;
      this.error = null;
      try {
        const params = {
            start_date: this.filters.startDate,
            end_date: this.filters.endDate,
            collector_its: this.filters.collectorIts,
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
    }
  },
};
</script>
