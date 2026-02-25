
const express = require('express');
const cors = require('cors');
const connectDB = require('./db');
require('dotenv').config();

const app = express();

// Middleware
app.use(cors());
app.use(express.json());

// Connect to Database
connectDB();

// Routes
app.get('/api/health', (req, res) => {
    res.json({ status: 'ok', db: 'connected', message: 'API is running' });
});

app.use('/api/admins', require('./routes/admins'));
app.use('/api/validate', require('./routes/validate'));
app.use('/api/transactions', require('./routes/transactions'));
app.use('/api/payment', require('./routes/payment'));

const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
