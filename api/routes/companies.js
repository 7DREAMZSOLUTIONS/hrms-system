const express = require('express');
const router = express.Router();
const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');

const Company = require('../models/Company');
const Device = require('../models/Device');
const Subscription = require('../models/Subscription');
const Employee = require('../models/Employee');
const Admin = require('../models/Admin');
const Transaction = require('../models/Transaction');

// GET /api/companies (fetch all)
router.get('/', async (req, res) => {
    try {
        const companies = await Company.find({}).sort({ createdAt: -1 });
        res.json({ success: true, data: companies });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// GET /api/companies/:id (fetch one with related data)
router.get('/:id', async (req, res) => {
    try {
        const companyId = req.params.id;
        const company = await Company.findOne({ companyId });
        if (!company) {
            return res.json({ success: false, message: 'Company not found' });
        }
        const device = await Device.findOne({ companyId });
        const admin = await Admin.findOne({ companyId });
        const employee = await Employee.findOne({ companyId, staffType: 'admin' });

        const responseData = {
            companyId: company.companyId,
            companyName: company.companyName,
            address: company.address || 'N/A',
            gstNumber: company.gstNumber || 'N/A',
            status: company.isActive ? 'Active' : 'Inactive',
            deviceId: device?.deviceId || 'N/A',
            subscriptionExpiry: device?.subscriptionExpiry || 'N/A',
            adminName: admin?.fullName || 'N/A',
            adminEmail: admin?.email || 'N/A',
            adminPhone: admin?.phone || 'N/A',
            empCode: employee?.empCode || 'N/A'
        };
        res.json({ success: true, data: responseData });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// POST /api/companies (add new company)
router.post('/', async (req, res) => {
    try {
        const data = req.body;
        const now = new Date();
        const companyId = data.eCid || 'COMP' + Date.now();
        const companyName = data.name || 'Unknown';
        const planType = data.planType || 'Starter';

        // Create Company
        await Company.create({
            companyId,
            companyName,
            isActive: true,
            address: data.addr || '',
            gstNumber: data.gst || '',
            officeTimings: { startTime: '10:00', endTime: '18:00' },
            weekends: { sundayOff: true, saturday: { offWeeks: [2, 4] } },
            planType
        });

        // Create Device
        if (data.did) {
            await Device.create({
                companyId,
                deviceId: data.did,
                subscriptionExpiry: data.sExp || null,
                planType
            });
        }

        // Create Subscription
        await Subscription.create({
            company_id: companyId,
            company_name: companyName,
            plan_type: planType,
            num_users: 0,
            subscription_amount: 0,
            status: 'Active',
            next_subscription_date: data.sExp || null,
            created_at: now
        });

        // Default admin password
        const defaultPassword = await bcrypt.hash('123456', 10);

        // Create Employee
        const empCode = data.eCode || ('ADM-' + Date.now());
        const emp = await Employee.create({
            companyId,
            company_id: companyId,
            companyName,
            name: data.eName || 'Admin',
            empCode,
            email: data.eEmail || 'N/A',
            mobile_number: data.ePhone || '',
            phone: data.ePhone || '',
            password: defaultPassword,
            staffType: 'admin',
            salary: 0,
            isDeleted: false,
            planType
        });

        // Create Admin
        await Admin.create({
            companyId,
            companyName,
            fullName: data.eName || 'Admin',
            email: data.eEmail || 'N/A',
            phone: data.ePhone || '',
            password: defaultPassword,
            role: 'hr_admin',
            employeeId: emp._id,
            planType
        });

        res.json({ success: true, message: 'Company added successfully' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// PUT /api/companies/:id
router.put('/:id', async (req, res) => {
    try {
        const companyId = req.params.id;
        const data = req.body;
        const companyName = data.name || 'Unknown';

        await Company.updateOne({ companyId }, {
            $set: {
                companyName: companyName,
                address: data.addr || '',
                gstNumber: data.gst || ''
            }
        });

        if (data.did) {
            await Device.updateOne({ companyId }, {
                $set: {
                    deviceId: data.did,
                    subscriptionExpiry: data.sExp || null
                }
            }, { upsert: true });
        }

        await Subscription.updateOne({ company_id: companyId }, {
            $set: {
                company_name: companyName,
                next_subscription_date: data.sExp || null
            }
        });

        if (data.eEmail || data.eName) {
            await Admin.updateMany({ companyId }, {
                $set: {
                    fullName: data.eName || 'Admin',
                    email: data.eEmail || 'N/A',
                    phone: data.ePhone || '',
                    companyName: companyName
                }
            });
        }

        if (data.eCode || data.eName) {
            const empUpdate = {
                name: data.eName || 'Admin',
                empCode: data.eCode || 'ADM-' + Date.now(),
                mobile_number: data.ePhone || '',
                phone: data.ePhone || '',
                companyName: companyName
            };
            if (data.eEmail) empUpdate.email = data.eEmail;

            await Employee.updateMany({ $or: [{ companyId }, { company_id: companyId }], staffType: 'admin' }, {
                $set: empUpdate
            });
        }

        res.json({ success: true, message: 'Company updated successfully' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// DELETE /api/companies/:id
router.delete('/:id', async (req, res) => {
    try {
        const companyId = req.params.id;

        const company = await Company.findOne({ companyId });
        if (!company) {
            return res.json({ success: false, message: 'Company not found' });
        }

        await Company.deleteOne({ companyId });
        await Admin.deleteMany({ companyId });
        await Employee.deleteMany({ companyId });
        await Employee.deleteMany({ company_id: companyId });
        await Device.deleteMany({ companyId });
        await Subscription.deleteMany({ company_id: companyId });
        await Transaction.deleteMany({ companyId });

        res.json({ success: true, message: 'Deleted successfully' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;
