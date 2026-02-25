
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

module.exports = router;
