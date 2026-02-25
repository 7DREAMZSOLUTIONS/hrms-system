const mongoose = require('mongoose');

const adminSchema = new mongoose.Schema({
    companyId: { type: String, description: "Custom Company ID" },
    fullName: { type: String },
    name: { type: String },
    phone: { type: String, required: true },
    companyName: { type: String },
    email: { type: String },
    password: { type: String },
    role: { type: String },
    created_at: { type: Date, default: Date.now }
}, { collection: 'admins' });

module.exports = mongoose.model('Admin', adminSchema);
