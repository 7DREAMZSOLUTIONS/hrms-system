
const mongoose = require('mongoose');

const adminSchema = new mongoose.Schema({
    companyId: { type: String, description: "Custom Company ID" },
    fullName: { type: String, required: true },
    phone: { type: String, required: true },
    companyName: { type: String, required: true },
    email: { type: String }
}, { collection: 'admins' });

module.exports = mongoose.model('Admin', adminSchema);
