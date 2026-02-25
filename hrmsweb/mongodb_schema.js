/**
 * MongoDB Schema Reference for HRMS Application
 * Database: hrms
 */

/* -------------------------------------------------------------------------- */
/* 1. Collection: admins                                                      */
/* -------------------------------------------------------------------------- */
// Stores company administrator details, subscription status, and plan info.
db.createCollection("admins", {
    validator: {
        $jsonSchema: {
            bsonType: "object",
            required: ["fullName", "phone", "companyName"],
            properties: {
                _id: {
                    bsonType: "objectId",
                    description: "Unique identifier for the admin/company document"
                },
                companyId: {
                    bsonType: "string",
                    description: "Custom Company ID (e.g., 'COMP001'). If missing, _id is used."
                },
                fullName: {
                    bsonType: "string",
                    description: "Name of the administrator"
                },
                phone: {
                    bsonType: "string",
                    description: "Registered mobile number (used for lookup)"
                },
                companyName: {
                    bsonType: "string",
                    description: "Name of the company"
                },
                email: {
                    bsonType: "string",
                    description: "Email address"
                },
                // -- Subscription Fields --
                num_users: {
                    bsonType: "int",
                    description: "Number of employees allowed/purchased"
                },
                plan_type: {
                    bsonType: "string",
                    description: "Name of the plan (e.g., 'Starter', 'Growth')"
                },
                subscription_amount: {
                    bsonType: "double",
                    description: "Amount paid for the current subscription"
                },
                last_payment_date: {
                    bsonType: "string",
                    description: "Date of last payment (Format: YYYY-MM-DD)"
                },
                next_subscription_date: {
                    bsonType: "string",
                    description: "Expiry date of subscription (Format: YYYY-MM-DD)"
                },
                status: {
                    bsonType: "string",
                    description: "Account status (e.g., 'Active', 'Inactive')"
                },
                updated_at: {
                    bsonType: "date",
                    description: "Timestamp of last update"
                }
            }
        }
    }
});

/* -------------------------------------------------------------------------- */
/* 2. Collection: transaction_history                                         */
/* -------------------------------------------------------------------------- */
// Logs all payment transactions.
db.createCollection("transaction_history", {
    validator: {
        $jsonSchema: {
            bsonType: "object",
            required: ["payment_id", "companyId", "amount"],
            properties: {
                _id: {
                    bsonType: "objectId"
                },
                payment_id: {
                    bsonType: "string",
                    description: "Razorpay or Gateway Payment ID"
                },
                companyId: {
                    bsonType: "string",
                    description: "Reference to admins.companyId (e.g., 'COMP001')"
                },
                company_name: {
                    bsonType: "string"
                },
                mobile: {
                    bsonType: "string"
                },
                email: {
                    bsonType: "string"
                },
                amount: {
                    bsonType: "double",
                    description: "Transaction amount"
                },
                plan_type: {
                    bsonType: "string"
                },
                num_employees: {
                    bsonType: "int"
                },
                payment_date: {
                    bsonType: "string",
                    description: "Date of payment (Format: YYYY-MM-DD)"
                },
                currency: {
                    bsonType: "string",
                    description: "Currency code (e.g., 'INR')"
                },
                status: {
                    bsonType: "string",
                    description: "Transaction status (e.g., 'Success')"
                },
                created_at: {
                    bsonType: "date",
                    description: "Timestamp of creation"
                }
            }
        }
    }
});

/* -------------------------------------------------------------------------- */
/* 3. Collection: devices                                                     */
/* -------------------------------------------------------------------------- */
// Stores device information linked to companies.
db.createCollection("devices", {
    validator: {
        $jsonSchema: {
            bsonType: "object",
            required: ["companyId"],
            properties: {
                _id: {
                    bsonType: "objectId"
                },
                companyId: {
                    bsonType: "string",
                    description: "Reference to admins.companyId (e.g., 'COMP001')"
                },
                subscriptionExpiry: {
                    bsonType: "string",
                    description: "Date when the device subscription expires (Format: YYYY-MM-DD)"
                },
                updated_at: {
                    bsonType: "date",
                    description: "Timestamp of last update"
                },
                // Other device specific fields...
            }
        }
    }
});
