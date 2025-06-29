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
                <label>Donation Type:</label>
                <div class="button-grid">
                    <button 
                        type="button"
                        v-for="type in donationTypes" 
                        :key="type.id"
                        @click="selectDonationType(type.id)"
                        :class="['option-btn', { 'selected': form.donation_type_id == type.id }]"
                    >
                        {{ type.name }}
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label>Currency:</label>
                <div class="button-grid">
                    <button 
                        type="button"
                        v-for="currency in currencies" 
                        :key="currency.id"
                        @click="selectCurrency(currency.id)"
                        :class="['option-btn', { 'selected': form.currency_id == currency.id }]"
                    >
                        {{ currency.symbol }} {{ currency.name }}
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="amount">Amount:</label>
                <input 
                    type="text" 
                    id="amount" 
                    v-model="displayAmount" 
                    required 
                    class="form-control amount-input"
                    placeholder="0"
                    inputmode="numeric" 
                />
            </div>

            <div v-if="selectedDonationType && selectedDonationType.tracks_count" class="form-group">
                <label for="quantity">Quantity:</label>
                <input 
                    type="number" 
                    id="quantity" 
                    v-model.number="form.quantity"
                    min="1"
                    required
                    class="form-control"
                    placeholder="1"
                />
            </div>

            <button type="submit" :disabled="loading || !isFormValid" class="submit-btn">
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
import { donationDefaults } from '../config.js';

export default {
    name: 'RecordDonation',
    data() {
        return {
            form: {
                donor_its_id: '',
                donation_type_id: null,
                currency_id: null,
                amount: '',
                quantity: 1, // Add quantity to form data
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
    computed: {
        // New computed property to easily access the selected donation type object
        selectedDonationType() {
            if (!this.form.donation_type_id) {
                return null;
            }
            return this.donationTypes.find(type => type.id === this.form.donation_type_id);
        },
        isFormValid() {
            return this.form.donor_its_id && this.form.donation_type_id && this.form.currency_id && this.form.amount;
        },
        displayAmount: {
            get() {
                if (this.form.amount === '' || this.form.amount === null || isNaN(this.form.amount)) {
                    return '';
                }
                // Format with commas, as a whole number.
                return Math.round(this.form.amount).toLocaleString('en-US');
            },
            set(value) {
                // Remove non-digit characters and parse.
                const numericValue = parseInt(String(value).replace(/[^0-9]/g, ''), 10);
                this.form.amount = isNaN(numericValue) ? '' : numericValue;
            }
        }
    },
    mounted() {
        this.fetchDonationTypes();
        this.fetchCurrencies();
    },
    methods: {
        async fetchDonationTypes() {
            try {
                const response = await window.axios.get('/api/donation-types');
                this.donationTypes = response.data;
            } catch (error) {
                console.error('Error fetching donation types:', error);
            }
        },
        async fetchCurrencies() {
            try {
                const response = await window.axios.get('/api/currencies');
                this.currencies = response.data;
                const lkrCurrency = this.currencies.find(c => c.code === 'LKR');
                if (lkrCurrency) {
                    this.selectCurrency(lkrCurrency.id);
                }
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
                const response = await window.axios.get(`/api/donors/${currentItsId}`);
                this.donorInfo = response.data;
            } catch (error) {
                console.error('Error looking up donor:', error);
                this.donorInfo = { fullname: null, email: null };
            } finally {
                this.loadingDonor = false;
            }
        },
        selectDonationType(id) {
            this.form.donation_type_id = id;
            this.updateAmount();

            // Reset quantity to 1 if the newly selected type does not track quantity
            const selectedType = this.donationTypes.find(type => type.id === id);
            if (selectedType && !selectedType.tracks_count) {
                this.form.quantity = 1;
            }
        },
        selectCurrency(id) {
            this.form.currency_id = id;
            this.updateAmount();
        },
        updateAmount() {
            const { donation_type_id, currency_id } = this.form;

            if (donation_type_id && currency_id) {
                const selectedType = this.donationTypes.find(type => type.id === donation_type_id);
                const selectedCurrency = this.currencies.find(curr => curr.id === currency_id);

                if (selectedType && selectedCurrency) {
                    const typeName = selectedType.name;
                    const currencyCode = selectedCurrency.code; // Use code instead of name

                    if (donationDefaults[typeName] && donationDefaults[typeName][currencyCode]) {
                        this.form.amount = donationDefaults[typeName][currencyCode];
                    }
                }
            }
        },
        async submitDonation() {
            this.loading = true;
            this.message = ''; // Clear previous messages
            
            try {
                await window.axios.post('/api/donations', this.form);
                
                this.message = 'Donation recorded successfully!';
                this.messageClass = 'success';

                // Reset the entire form for the next donation
                this.form.donor_its_id = '';
                this.form.donation_type_id = null;
                this.form.currency_id = null;
                this.form.amount = '';
                this.form.quantity = 1; // Reset quantity
                this.donorInfo = { fullname: null, email: null };

                // Clear the success message after 3 seconds
                setTimeout(() => {
                    this.message = '';
                }, 3000);

            } catch (error) {
                this.message = error.response?.data?.message || 'Error recording donation';
                this.messageClass = 'error';
                 // Clear the error message after 5 seconds
                setTimeout(() => {
                    this.message = '';
                }, 5000);
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

.button-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
    margin-top: 10px;
}



.option-btn {
    padding: 15px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    background-color: #ffffff;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    transition: all 0.2s ease;
    text-align: center;
    min-height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.option-btn:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.option-btn.selected {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
    box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
}

.amount-input {
    text-align: right;
    font-size: 18px;
    font-weight: bold;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .button-grid {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    .option-btn {
        padding: 18px 15px;
        font-size: 18px;
        min-height: 55px;
    }
    
    .form-control {
        font-size: 16px;
        padding: 12px;
    }
    
    .amount-input {
        font-size: 20px;
        padding: 15px;
    }
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
