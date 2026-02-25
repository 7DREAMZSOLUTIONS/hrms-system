const mongoose = require('mongoose');
require('dotenv').config();
const Device = require('./models/Device');

mongoose.connect(process.env.MONGO_URI, { useNewUrlParser: true, useUnifiedTopology: true })
    .then(async () => {
        const devices = await Device.find({}).lean();
        console.log("DEVICES:", devices);
        process.exit(0);
    });
