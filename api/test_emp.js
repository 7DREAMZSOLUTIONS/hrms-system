const mongoose = require('mongoose');
require('dotenv').config();
const Employee = require('./models/Employee');

mongoose.connect(process.env.MONGO_URI, { useNewUrlParser: true, useUnifiedTopology: true })
    .then(async () => {
        const emps = await Employee.find({ companyId: "7DMZ000" }).lean();
        console.log("EMPLOYEES:", emps);
        process.exit(0);
    });
