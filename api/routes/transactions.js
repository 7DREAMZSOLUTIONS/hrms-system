
const express = require('express');
const router = express.Router();
const Transaction = require('../models/Transaction');

// GET /api/transactions?company_id=...
router.get('/', async (req, res) => {
    try {
        const { company_id } = req.query;

        if (!company_id) {
            // If no company ID, fetch all transactions (for superadmin dashboard)
            const transactions = await Transaction.find({})
                .sort({ created_at: -1 })
                .limit(50);
            return res.json({ success: true, data: transactions });
        }

        const transactions = await Transaction.find({ companyId: company_id })
            .sort({ created_at: -1 })
            .limit(10);

        const formattedTransactions = transactions.map(txn => {
            let dateStr = 'N/A';
            if (txn.payment_date) {
                const date = new Date(txn.payment_date);
                if (!isNaN(date.getTime())) {
                    dateStr = date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                } else {
                    dateStr = txn.payment_date;
                }
            } else if (txn.created_at) {
                dateStr = new Date(txn.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            }

            return {
                payment_id: txn.payment_id || 'N/A',
                amount: txn.amount || 0,
                plan_type: txn.plan_type || 'N/A',
                num_employees: txn.num_employees || 0,
                status: txn.status || 'Unknown',
                date: dateStr
            };
        });

        res.json({ success: true, data: formattedTransactions });

    } catch (err) {
        res.status(500).json({ success: false, message: "Error fetching transactions: " + err.message });
    }
});

module.exports = router;
