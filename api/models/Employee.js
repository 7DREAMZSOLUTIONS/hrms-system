
const mongoose = require('mongoose');

const employeeSchema = new mongoose.Schema({
    companyId: { type: String, required: true }, // Note: schema says company_id but migration code used companyId in filter
    company_id: String, // Keeping both potential fields to match PHP logic/schema ambiguity
    name: String,
    empCode: String,
    email: String,
    mobile_number: String,
    staffType: String
}, { collection: 'employees' });

module.exports = mongoose.model('Employee', employeeSchema);
