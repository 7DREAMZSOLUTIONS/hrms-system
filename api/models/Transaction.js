
const mongoose = require('mongoose');

const transactionSchema = new mongoose.Schema({
    payment_id: { type: String, required: true },
    invoice_number: String,
    companyId: { type: String, required: true },
    company_name: String,
    mobile: String,
    email: String, // Kept for history if needed, though schema said removed
    amount: { type: Number, required: true },
    base_amount: Number,
    gst_amount: Number,
    plan_type: String,
    num_employees: Number,
    payment_date: String,
    currency: String,
    status: String,
    created_at: { type: Date, default: Date.now }
}, { collection: 'transaction_history' });

module.exports = mongoose.model('Transaction', transactionSchema);
