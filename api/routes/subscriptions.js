const express = require('express');
const router = express.Router();
const Subscription = require('../models/Subscription');
const Company = require('../models/Company');
const Device = require('../models/Device');

// GET /api/subscriptions
router.get('/', async (req, res) => {
    try {
        const subscriptions = await Subscription.find({}).lean();
        const devices = await Device.find({}).lean();

        // Map devices by companyId
        const deviceMap = {};
        devices.forEach(d => {
            deviceMap[d.companyId] = d.deviceId;
        });

        // Attach deviceId to subscription response
        const enhancedSubscriptions = subscriptions.map(sub => {
            return {
                ...sub,
                deviceId: deviceMap[sub.company_id] || 'N/A'
            };
        });

        res.json({ success: true, data: enhancedSubscriptions });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// PUT /api/subscriptions/:companyId
router.put('/:companyId', async (req, res) => {
    try {
        const companyId = req.params.companyId;
        const { action, planType, expiryDate } = req.body;

        if (action === 'update') {
            await Subscription.updateOne({ company_id: companyId }, {
                $set: {
                    plan_type: planType,
                    next_subscription_date: expiryDate,
                    updated_at: new Date()
                }
            });
            await Company.updateOne({ companyId }, {
                $set: { planType, updatedAt: new Date() }
            });
            await Device.updateMany({ companyId }, {
                $set: { subscriptionExpiry: expiryDate, updatedAt: new Date() }
            });
        } else if (action === 'cancel') {
            await Subscription.updateOne({ company_id: companyId }, {
                $set: { status: 'Cancelled', updated_at: new Date() }
            });
        } else if (action === 'activate') {
            await Subscription.updateOne({ company_id: companyId }, {
                $set: { status: 'Active', updated_at: new Date() }
            });
        } else {
            return res.json({ success: false, message: 'Invalid action specified' });
        }

        res.json({ success: true, message: 'Subscription updated' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;
