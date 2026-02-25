
const mongoose = require('mongoose');

const subscriptionSchema = new mongoose.Schema({
    company_id: { type: String, required: true },
    company_name: String,
    plan_type: String,
    num_users: Number,
    subscription_amount: Number,
    next_subscription_date: String,
    last_payment_date: String,
    status: String,
    created_at: Date,
    updated_at: Date
}, { collection: 'subscription' });

module.exports = mongoose.model('Subscription', subscriptionSchema);
