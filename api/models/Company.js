const mongoose = require('mongoose');

const companySchema = new mongoose.Schema({
    companyId: { type: String, required: true },
    companyName: { type: String, required: true },
    isActive: { type: Boolean, default: true },
    address: String,
    gstNumber: String,
    officeTimings: {
        startTime: String,
        endTime: String
    },
    weekends: {
        sundayOff: Boolean,
        saturday: {
            offWeeks: [Number]
        }
    },
    planType: String
}, { collection: 'companies', timestamps: true });

module.exports = mongoose.model('Company', companySchema);
