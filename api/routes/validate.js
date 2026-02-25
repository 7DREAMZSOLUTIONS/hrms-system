
const express = require('express');
const router = express.Router();
const Admin = require('../models/Admin');
const Subscription = require('../models/Subscription');

// POST /api/validate
router.post('/', async (req, res) => {
    try {
        const { mobile_number } = req.body;

        if (!mobile_number) {
            return res.json({ success: false, message: "Mobile number is required" });
        }

        // 1. Search in Admins
        const admin = await Admin.findOne({ phone: mobile_number });

        if (!admin) {
            return res.json({ success: false, message: "Mobile number not found in Admins" });
        }

        const companyIdStr = admin.companyId || admin._id.toString();

        // 2. Fetch Subscription
        const sub = await Subscription.findOne({ company_id: companyIdStr });

        const responseData = {
            sno: admin._id,
            companyId: companyIdStr,
            company_name: admin.companyName || 'Unknown Company',
            mobile_number: admin.phone || mobile_number,
            email: admin.email || 'N/A',

            // Subscription Details
            num_employees: sub?.num_users || 10,
            plan_type: sub?.plan_type || 'Starter',
            subscription_amount: sub?.subscription_amount || 3000,
            next_subscription_date: sub?.next_subscription_date || 'N/A',
            last_payment_date: sub?.last_payment_date || 'N/A',
            status: sub?.status || 'Active'
        };

        res.json({ success: true, data: responseData });

    } catch (err) {
        res.status(500).json({ success: false, message: "Error connecting to MongoDB: " + err.message });
    }
});

module.exports = router;
