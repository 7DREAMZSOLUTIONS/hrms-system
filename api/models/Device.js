
const mongoose = require('mongoose');

const deviceSchema = new mongoose.Schema({
    companyId: { type: String, required: true },
    subscriptionExpiry: String
    // Add other fields as necessary
}, { collection: 'devices' });

module.exports = mongoose.model('Device', deviceSchema);
