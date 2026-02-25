const express = require('express');
const router = express.Router();
const Employee = require('../models/Employee');

// GET /api/employees
router.get('/', async (req, res) => {
    try {
        const employees = await Employee.find({});
        res.json({ success: true, data: employees });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;
