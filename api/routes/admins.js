const express = require('express');
const router = express.Router();
const Admin = require('../models/Admin');

// GET /api/admins
router.get('/', async (req, res) => {
    try {
        const admins = await Admin.find({}, {
            _id: 1,
            fullName: 1,
            phone: 1,
            companyName: 1
        });
        res.json({ success: true, data: admins });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// POST /api/admins/register (For Super Admin)
router.post('/register', async (req, res) => {
    try {
        const { name, phone, password, role } = req.body;
        const existingUser = await Admin.findOne({ phone });

        if (existingUser) {
            return res.json({ success: false, message: 'Super Admin with this phone number already exists.' });
        }

        const newAdmin = new Admin({
            name,
            phone,
            password,
            role,
            created_at: new Date()
        });

        await newAdmin.save();
        res.json({ success: true, message: 'Admin registered successfully' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;
