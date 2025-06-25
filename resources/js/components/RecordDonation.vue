<template>
    <div class="record-donation">
        <h2>Record Donation</h2>
        
        <form @submit.prevent="submitDonation" class="donation-form">
            <div class="form-group">
                <label for="donor_its_id">Donor's ITS ID:</label>
                <input 
                    type="text" 
                    id="donor_its_id" 
                    v-model="form.donor_its_id" 
                    @keyup="onItsIdChange"
                    @blur="onItsIdChange"
                    maxlength="8"
                    pattern="[0-9]{8}"
                    placeholder="Enter 8-digit ITS ID"
                    required 
                    class="form-control"
                />
                <div v-if="donorInfo.fullname" class="donor-name">
                    <strong>Donor Name:</strong> {{ donorInfo.fullname }}
                </div>
                <div v-if="loadingDonor" class="loading-text">
                    Loading donor information...
                </div>
            </div>

            <div class="form-group">
                <label for="whatsapp_number">WhatsApp Number (Optional):</label>
                <input 
                    type="tel" 
                    id="whatsapp_number" 
                    v-model="form.whatsapp_number" 
                    placeholder="e.g., +1234567890"
                    class="form-control"
                />
            </div>

            <div class="form-group">
                <label for="donation_type_id">Donation Type:</label>
                <select id="donation_type_id" v-model="form.donation_type_id" required class="form-control">
                    <option value="">Select Donation Type</option>
                    <option v-for="type in donationTypes" :key="type.id" :value="type.id">
                        {{ type.name }}
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="currency_id">Currency:</label>
                <select id="currency_id" v-model="form.currency_id" required class="form-control">
                    <option value="">Select Currency</option>
                    <option v-for="currency in currencies" :key="currency.id" :value="currency.id">
                        {{ currency.name }} ({{ currency.symbol }})
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="amount">Amount:</label>
                <input 
                    type="number" 
                    id="amount" 
                    v-model="form.amount" 
                    step="0.01" 
                    min="0" 
                    required 
                    class="form-control"
                    placeholder="0.00"
                />
            </div>

            <div class="form-group">
                <label for="remarks">Remarks (Optional):</label>
                <textarea 
                    id="remarks" 
                    v-model="form.remarks" 
                    rows="3" 
                    class="form-control"
                    placeholder="Any additional notes..."
                ></textarea>
            </div>

            <button type="submit" :disabled="loading" class="submit-btn">
                <span v-if="loading" class="loading-spinner">⏳</span>
                <span v-else>💰</span>
                {{ loading ? 'Submitting...' : 'Submit Donation' }}
            </button>
        </form>

        <div v-if="message" :class="messageClass" class="message">
            {{ message }}
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'RecordDonation',
    data() {
        return {
            form: {
                donor_its_id: '',
                whatsapp_number: '',
                donation_type_id: '',
                currency_id: '',
                amount: '',
                remarks: ''
            },
            donationTypes: [],
            currencies: [],
            donorInfo: {
                fullname: null,
                email: null
            },
            loading: false,
            loadingDonor: false,
            message: '',
            messageClass: '',
            donorLookupTimeout: null
        };
    },
    mounted() {
        this.fetchDonationTypes();
        this.fetchCurrencies();
    },
    methods: {
        async fetchDonationTypes() {
            try {
                const response = await axios.get('/api/donation-types');
                this.donationTypes = response.data;
            } catch (error) {
                console.error('Error fetching donation types:', error);
            }
        },
        async fetchCurrencies() {
            try {
                const response = await axios.get('/api/currencies');
                this.currencies = response.data;
            } catch (error) {
                console.error('Error fetching currencies:', error);
            }
        },
        onItsIdChange() {
            // Clear previous timeout
            if (this.donorLookupTimeout) {
                clearTimeout(this.donorLookupTimeout);
            }
            
            const currentItsId = this.form.donor_its_id.trim();
            
            // Only reset donor info if the ITS ID is completely different or not 8 digits
            if (currentItsId.length < 8) {
                this.donorInfo = { fullname: null, email: null };
            }
            
            // Only lookup if we have exactly 8 digits
            if (currentItsId.length === 8 && /^\d{8}$/.test(currentItsId)) {
                this.donorLookupTimeout = setTimeout(() => {
                    this.lookupDonor();
                }, 300); // Reduced timeout to 300ms for better responsiveness
            }
        },
        async lookupDonor() {
            const currentItsId = this.form.donor_its_id.trim();
            if (currentItsId.length !== 8) {
                return;
            }
            
            this.loadingDonor = true;
            
            try {
                const response = await axios.get(`/api/donors/${currentItsId}`);
                this.donorInfo = response.data;
            } catch (error) {
                console.error('Error looking up donor:', error);
                this.donorInfo = { fullname: null, email: null };
            } finally {
                this.loadingDonor = false;
            }
        },
        async submitDonation() {
            this.loading = true;
            this.message = '';
            
            try {
                const response = await axios.post('/api/donations', this.form);
                this.message = 'Donation recorded successfully!';
                this.messageClass = 'success';
                
                // Reset form
                this.form = {
                    donor_its_id: '',
                    whatsapp_number: '',
                    donation_type_id: '',
                    currency_id: '',
                    amount: '',
                    remarks: ''
                };
                this.donorInfo = { fullname: null, email: null };
                
            } catch (error) {
                this.message = error.response?.data?.message || 'Error recording donation';
                this.messageClass = 'error';
                console.error('Error submitting donation:', error);
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>

<style scoped>
.record-donation {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.donation-form {
    background: #f9f9f9;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.donor-name {
    margin-top: 8px;
    padding: 10px;
    background: #e8f5e8;
    border: 1px solid #28a745;
    border-radius: 4px;
    color: #155724;
    font-size: 14px;
}

.loading-text {
    margin-top: 8px;
    color: #6c757d;
    font-style: italic;
    font-size: 14px;
}

.submit-btn {
    width: 100%;
    padding: 15px 20px;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.submit-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #218838, #1ea085);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.submit-btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.loading-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.message {
    margin-top: 20px;
    padding: 15px;
    border-radius: 4px;
    font-weight: bold;
}

.message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

h2 {
    text-align: center;
    color: #333;
    margin-bottom: 30px;
    font-size: 28px;
}
</style>
