
const express = require('express');
const router = express.Router();
const mongoose = require('mongoose');
const Transaction = require('../models/Transaction');
const Subscription = require('../models/Subscription');
const Admin = require('../models/Admin');
const Device = require('../models/Device');
const Employee = require('../models/Employee');

// Mock email function (since we can't easily port PHP mail logic without nodemailer setup)
const sendInvoiceEmail = async (email, name, details) => {
    console.log(`[MOCK EMAIL] Sending invoice to ${name} <${email}>`);
    console.log(details);
    // TODO: Implement actual email sending using nodemailer if credentials provided
};

// POST /api/payment
router.post('/', async (req, res) => {
    try {
        const {
            payment_id,
            company_sno, // frontend sends this but we validate mobile
            amount,
            plan_type,
            num_employees,
            company_name,
            mobile,
            email
        } = req.body;

        if (!mobile || !payment_id) {
            return res.json({ success: false, message: "Missing required payment or mobile information." });
        }

        // Step 0: Validate Admin
        const admin = await Admin.findOne({ phone: mobile });
        if (!admin) {
            return res.json({ success: false, message: `Admin not found for this mobile number: ${mobile}` });
        }

        const adminObjId = admin._id;
        const companyIdStr = admin.companyId || admin._id.toString();

        // Dates
        const currentDate = new Date();
        const nextYearDate = new Date();
        nextYearDate.setFullYear(currentDate.getFullYear() + 1);

        const current_date_str = currentDate.toISOString().split('T')[0];
        const next_year_date_str = nextYearDate.toISOString().split('T')[0];

        // GST Calculation
        const total_amount = parseFloat(amount);
        const base_amount = parseFloat((total_amount / 1.18).toFixed(2));
        const gst_amount = parseFloat((total_amount - base_amount).toFixed(2));
        const invoice_number = `INV-${currentDate.toISOString().slice(0, 10).replace(/-/g, '')}-${Math.floor(1000 + Math.random() * 9000)}`;

        // 1. Transaction History
        const transaction = new Transaction({
            payment_id,
            invoice_number,
            companyId: companyIdStr,
            company_name,
            mobile,
            amount: total_amount,
            base_amount,
            gst_amount,
            plan_type,
            num_employees: parseInt(num_employees),
            payment_date: current_date_str,
            currency: 'INR',
            status: 'Success',
            created_at: new Date()
        });
        await transaction.save();

        // 2. Subscription Update
        await Subscription.updateOne(
            { company_id: companyIdStr },
            {
                $set: {
                    company_name,
                    plan_type,
                    num_users: parseInt(num_employees),
                    subscription_amount: parseFloat(amount),
                    next_subscription_date: next_year_date_str,
                    last_payment_date: current_date_str,
                    status: 'Active',
                    updated_at: new Date()
                },
                $setOnInsert: { created_at: new Date() }
            },
            { upsert: true }
        );

        // Update Admin Email
        if (email) {
            await Admin.updateOne({ _id: adminObjId }, { $set: { email } });
        }

        // Operation C: Devices
        await Device.updateMany(
            { companyId: companyIdStr },
            {
                $set: {
                    companyId: companyIdStr,
                    subscriptionExpiry: next_year_date_str
                }
            }
        );

        // 3. Send Email (Mocked/Logged)
        // Find admins to email
        const adminEmployees = await Employee.find({
            companyId: companyIdStr, // Check schema field usage in Employee.js
            staffType: 'admin',
            email: { $ne: null }
        });

        const recipients = {};
        adminEmployees.forEach(emp => {
            if (emp.email) recipients[emp.email] = emp.name || 'Admin';
        });
        if (email && !recipients[email]) {
            recipients[email] = company_name || "Valued Customer";
        }

        for (const [rEmail, rName] of Object.entries(recipients)) {
            await sendInvoiceEmail(rEmail, rName, {
                invoice_number,
                payment_id,
                company_id: companyIdStr,
                company_name,
                plan_type,
                num_users: num_employees,
                amount: total_amount,
                date: current_date_str,
                next_date: next_year_date_str
            });
        }

        res.json({
            success: true,
            message: "Payment successful. Subscription updated & Activation emails sent to known admins.",
            next_subscription_date: next_year_date_str
        });

    } catch (err) {
        console.error(err);
        res.status(500).json({ success: false, message: "General Error: " + err.message });
    }
});

module.exports = router;
