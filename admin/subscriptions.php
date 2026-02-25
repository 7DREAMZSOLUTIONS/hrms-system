<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

require_once '../db_connect_mongo.php';

// Fetch Subscriptions
$subscriptions = [];
try {
    // Optional: Sort by created_at desc if field exists, for now just fetch all
    $query = new MongoDB\Driver\Query([], ['sort' => ['_id' => -1]]);
    $cursor = $mongoManager->executeQuery("$mongodb_name.subscription", $query);
    foreach ($cursor as $doc) {
        $subscriptions[] = $doc;
    }
} catch (Exception $e) {
    // Handle error quietly or log
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Management | HRMS Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;900&family=Inter:wght@300;700&display=swap');

        :root {
            --red: #FF0000;
            --black: #0A0A0A;
            --gray: #1F1F1F;
        }

        body {
            background-color: var(--black);
            font-family: 'Inter', sans-serif;
            color: white;
            margin: 0;
            overflow-x: hidden;
        }

        .glitch {
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 2px;
        }

        /* Sidebar Structure */
        .command-bar {
            width: 80px;
            background: var(--gray);
            border-right: 2px solid var(--red);
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1), transform 0.4s;
            z-index: 110;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }

        @media (max-width: 768px) {
            .command-bar {
                transform: translateX(-100%);
                width: 280px;
                box-shadow: 10px 0 30px rgba(0, 0, 0, 0.5);
            }

            .command-bar.open {
                transform: translateX(0);
            }

            .sidebar-backdrop {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.7);
                backdrop-filter: blur(5px);
                z-index: 105;
            }

            .sidebar-backdrop.open {
                display: block;
            }

            main {
                margin-left: 0 !important;
                padding: 15px !important;
                overflow-x: hidden;
            }

            .mobile-header {
                display: flex !important;
                background: rgba(31, 31, 31, 0.8) !important;
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
            }

            .nav-text {
                opacity: 1 !important;
                display: inline-block !important;
            }

            h1.glitch {
                font-size: 20px !important;
                letter-spacing: 1px !important;
            }

            p.text-gray-600 {
                letter-spacing: 2px !important;
                font-size: 8px !important;
            }

            /* Premium Card-based Industrial Table on Mobile */
            .industrial-table {
                display: block;
                background: transparent;
            }

            .industrial-table thead {
                display: none;
            }

            .industrial-table tbody {
                display: block;
                width: 100%;
            }

            .industrial-table tr {
                display: block;
                background: linear-gradient(145deg, #111, #0a0a0a);
                margin-bottom: 25px;
                padding: 20px;
                border: 1px solid #222;
                border-left: 3px solid var(--red);
                border-radius: 4px;
                box-shadow: inset 0 0 15px rgba(255, 0, 0, 0.05);
                position: relative;
                overflow: hidden;
            }

            .industrial-table tr::after {
                content: "";
                position: absolute;
                top: 0;
                right: 0;
                width: 40px;
                height: 40px;
                background: linear-gradient(45deg, transparent 50%, rgba(255, 0, 0, 0.1) 50%);
            }

            .industrial-table td {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                padding: 12px 0;
                border: none !important;
                width: 100%;
                text-align: left;
                border-bottom: 1px solid rgba(255, 255, 255, 0.03) !important;
            }

            .industrial-table td>div,
            .industrial-table td>span {
                max-width: 100%;
                word-wrap: break-word;
                margin-top: 4px;
            }

            .industrial-table td:last-child {
                border-bottom: none !important;
                margin-top: 10px;
                padding-top: 20px;
                flex-direction: row;
                justify-content: center;
                gap: 20px;
            }

            .industrial-table td::before {
                content: attr(data-label);
                flex-shrink: 0;
                font-family: 'Orbitron', sans-serif;
                font-size: 8px;
                color: var(--red);
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 2px;
                margin-bottom: 2px;
            }
        }

        @media (min-width: 769px) {
            .command-bar:hover {
                width: 240px;
            }

            .command-bar:hover .nav-text {
                opacity: 1;
                display: inline-block;
            }

            main {
                margin-left: 80px;
                transition: margin-left 0.4s;
            }

            .command-bar:hover~main {
                margin-left: 240px;
            }

            .mobile-header {
                display: none;
            }
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 20px;
            color: #666;
            text-decoration: none;
            border-left: 4px solid transparent;
            white-space: nowrap;
            transition: 0.3s;
        }

        .nav-item.active,
        .nav-item:hover {
            color: white;
            background: rgba(255, 0, 0, 0.1);
            border-left: 4px solid var(--red);
        }

        .nav-text {
            opacity: 0;
            display: none;
            margin-left: 20px;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
        }

        .mobile-header {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(31, 31, 31, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 2px solid var(--red);
            padding: 15px 20px;
            z-index: 100;
            align-items: center;
            justify-content: space-between;
            display: none;
        }

        /* Table Design */
        .industrial-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .industrial-table thead tr {
            color: var(--red);
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .industrial-table tr {
            background: #111;
            transition: 0.3s;
        }

        .industrial-table td,
        .industrial-table th {
            padding: 20px;
            text-align: left;
        }

        .industrial-table td {
            border-top: 1px solid #222;
            border-bottom: 1px solid #222;
        }

        .industrial-table tr:hover {
            background: #161616;
            border-left: 4px solid var(--red);
        }

        /* Status Pills */
        .status-pill {
            padding: 4px 10px;
            font-size: 9px;
            font-family: 'Orbitron', sans-serif;
            text-transform: uppercase;
            font-weight: 900;
            border-radius: 2px;
            letter-spacing: 1px;
        }

        .status-active {
            background: rgba(0, 255, 0, 0.1);
            color: #00FF00;
            border: 1px solid #00FF00;
        }

        .status-overdue {
            background: rgba(255, 0, 0, 0.1);
            color: #FF0000;
            border: 1px solid #FF0000;
        }

        .status-expiring {
            background: rgba(255, 255, 0, 0.1);
            color: #FFFF00;
            border: 1px solid #FFFF00;
        }

        .btn-action {
            background: var(--red);
            color: white;
            font-family: 'Orbitron', sans-serif;
            padding: 12px 25px;
            font-size: 11px;
            font-weight: bold;
            transition: 0.3s;
            letter-spacing: 1px;
        }

        .btn-action:hover {
            background: #cc0000;
            box-shadow: 0 0 15px var(--red);
        }
    </style>
</head>

<body class="flex flex-col md:flex-row">

    <?php include 'slidebar.php'; ?>

    <main class="flex-1 p-6 md:p-12">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12">
            <div>
                <h1 class="glitch text-3xl font-black italic uppercase">Subscription_Hub</h1>
                <p class="text-gray-600 text-[10px] tracking-[4px] mt-1">PLAN_MANAGEMENT // ACCESS_MV</p>
            </div>
            <!-- <button onclick="renewSubscription()" class="btn-action mt-6 md:mt-0 w-full md:w-auto">
                <i class="fas fa-plus mr-2"></i> RENEW SUBSCRIPTION
            </button> -->
        </header>

        <div class="overflow-x-auto">
            <table class="industrial-table">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Last Payment</th>
                        <th>Expiry Date</th>
                        <th>Users</th>
                        <th>Plan Type</th>
                        <th>Sub. Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="subscriptionTableBody">
                    <?php if (empty($subscriptions)): ?>
                        <tr>
                            <td colspan="8" class="text-center" style="text-align: center;">
                                <div class="flex flex-col items-center justify-center p-8 text-gray-400 font-mono w-full">
                                    <i class="fas fa-box-open opacity-50 text-4xl mb-4 text-red-600 block"></i>
                                    <span>NO_SUBSCRIPTION_DATA_FOUND</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subscriptions as $sub):
                            $companyName = htmlspecialchars($sub->company_name ?? 'Unknown Company');
                            $companyId = htmlspecialchars($sub->company_id ?? 'N/A');

                            $lastPayment = $sub->last_payment_date ?? 'N/A';
                            $nextPayment = $sub->next_subscription_date ?? 'N/A';

                            $numUsers = isset($sub->num_users) ? (int) $sub->num_users : 0;
                            $planType = htmlspecialchars($sub->plan_type ?? 'N/A');
                            $subAmount = isset($sub->subscription_amount) ? number_format((float) $sub->subscription_amount, 2) : '0.00';

                            $statusRaw = $sub->status ?? 'Unknown';
                            $statusVisual = 'status-active';

                            if (strtolower($statusRaw) === 'overdue') {
                                $statusVisual = 'status-overdue';
                            } else if (strtolower($statusRaw) === 'expiring' || strtolower($statusRaw) === 'inactive') {
                                $statusVisual = 'status-expiring';
                            } else if (strtolower($statusRaw) !== 'active') {
                                $statusVisual = 'status-expiring';
                            }
                            ?>
                            <tr>
                                <td data-label="Company Name">
                                    <div class="font-bold text-white">
                                        <?php echo $companyName; ?>
                                    </div>
                                    <div class="text-[9px] text-gray-500 uppercase mt-1">ID:
                                        <?php echo $companyId; ?>
                                    </div>
                                </td>
                                <td data-label="Last Payment">
                                    <div class="text-xs">
                                        <?php echo $lastPayment; ?>
                                    </div>
                                </td>
                                <td data-label="Expiry Date">
                                    <div
                                        class="text-xs <?php echo ($statusVisual === 'status-overdue' || $statusVisual === 'status-expiring') ? 'text-red-600' : 'text-green-500'; ?> font-bold">
                                        <?php echo $nextPayment; ?>
                                    </div>
                                </td>
                                <td data-label="Users" class="font-mono text-sm">
                                    <?php echo $numUsers; ?>
                                </td>
                                <td data-label="Plan Type">
                                    <div
                                        class="status-pill inline-block bg-gray-800 text-white border border-gray-600 px-2 py-1 flex items-center justify-center min-w-max">
                                        <?php echo str_replace(' ', '_', strtoupper($planType)); ?>
                                    </div>
                                </td>
                                <td data-label="Sub. Amount">
                                    <div class="text-sm font-black text-red-400">₹
                                        <?php echo $subAmount; ?>
                                    </div>
                                </td>
                                <td data-label="Status">
                                    <span class="status-pill <?php echo $statusVisual; ?>">
                                        <?php echo strtoupper($statusRaw); ?>
                                    </span>
                                </td>
                                <td data-label="Actions">
                                    <!-- <button onclick="downloadInvoice('<?php echo $companyId; ?>')"
                                        class="text-gray-600 hover:text-white transition mr-4" title="Download Invoice">
                                        <i class="fas fa-download"></i>
                                    </button> -->
                                    <button
                                        onclick="openSubscriptionActions('<?php echo $companyId; ?>', '<?php echo $planType; ?>', '<?php echo $nextPayment; ?>', '<?php echo $statusRaw; ?>')"
                                        class="text-gray-600 hover:text-white transition mr-4">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Subscription Actions Modal -->
        <div id="subscriptionActionsModal"
            class="fixed inset-0 bg-black/90 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
            <div class="bg-[#111] border border-[#333] border-t-4 border-t-red-600 w-full max-w-md animation-slide-up">
                <div class="p-6 flex justify-between items-center border-b border-gray-800">
                    <h2 class="glitch text-sm text-red-600 uppercase">SUB_CONTROL_HUB</h2>
                    <button onclick="closeSubscriptionModal()" class="text-gray-600 hover:text-white transition"><i
                            class="fas fa-times"></i></button>
                </div>
                <div class="p-8 space-y-6">
                    <input type="hidden" id="manageCompanyId">
                    <input type="hidden" id="manageCurrentStatus">

                    <div>
                        <label class="text-[9px] text-gray-500 mb-2 block uppercase font-bold tracking-widest">Plan
                            Selection</label>
                        <select id="managePlanType"
                            class="w-full bg-[#1A1A1A] border-2 border-[#333] p-4 text-xs font-mono text-white focus:outline-none focus:border-red-600 transition tracking-widest uppercase">
                            <option value="Starter">Starter</option>
                            <option value="Pro">Pro</option>
                            <option value="Enterprise">Enterprise</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[9px] text-gray-500 mb-2 block uppercase font-bold tracking-widest">Expiry
                            Override</label>
                        <input type="date" id="manageExpiryDate"
                            class="w-full bg-[#1A1A1A] border-2 border-[#333] p-4 text-xs font-mono text-white focus:outline-none focus:border-red-600 transition uppercase tracking-widest">
                    </div>

                    <div class="pt-6 border-t border-[#222] flex flex-col gap-4">
                        <button onclick="saveSubscriptionChanges()"
                            class="w-full bg-red-600 text-white font-['Orbitron'] py-3 text-xs tracking-widest font-bold hover:bg-red-700 transition">
                            <i class="fas fa-save mr-2"></i>SAVE PARAMETERS
                        </button>

                        <button id="toggleStatusBtn" onclick="toggleSubscriptionStatus()"
                            class="w-full bg-transparent border-2 border-red-600/30 text-red-500 font-['Orbitron'] py-3 text-xs tracking-widest font-bold hover:bg-red-600/10 hover:border-red-600 transition">
                            <i class="fas fa-ban mr-2" id="toggleStatusIcon"></i><span id="toggleStatusText">TERMINATE
                                SUBSCRIPTION</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('backdrop').classList.toggle('open');
        }

        function renewSubscription() {
            alert("INITIATING_RENEWAL_PROTOCOL...\n\nConnecting to payment gateway for authorization.");
        }

        function openSubscriptionActions(companyId, currentPlan, currentExpiry, currentStatus) {
            document.getElementById('manageCompanyId').value = companyId;
            document.getElementById('manageCurrentStatus').value = currentStatus;

            // Update fields with current values
            document.getElementById('managePlanType').value = currentPlan && currentPlan !== 'N/A' ? currentPlan : 'Starter';

            // Format expiry date correctly for input type="date" (YYYY-MM-DD)
            document.getElementById('manageExpiryDate').value = currentExpiry && currentExpiry !== 'N/A' ? currentExpiry : '';

            // Update toggle button UI
            const toggleBtn = document.getElementById('toggleStatusBtn');
            const toggleIcon = document.getElementById('toggleStatusIcon');
            const toggleText = document.getElementById('toggleStatusText');

            if (currentStatus && (currentStatus.toLowerCase() === 'cancelled' || currentStatus.toLowerCase() === 'inactive')) {
                toggleBtn.className = "w-full bg-transparent border-2 border-green-500/30 text-green-500 font-['Orbitron'] py-3 text-xs tracking-widest font-bold hover:bg-green-500/10 hover:border-green-500 transition";
                toggleIcon.className = "fas fa-check-circle mr-2";
                toggleText.textContent = "ACTIVATE SUBSCRIPTION";
            } else {
                toggleBtn.className = "w-full bg-transparent border-2 border-red-600/30 text-red-500 font-['Orbitron'] py-3 text-xs tracking-widest font-bold hover:bg-red-600/10 hover:border-red-600 transition";
                toggleIcon.className = "fas fa-ban mr-2";
                toggleText.textContent = "TERMINATE SUBSCRIPTION";
            }

            document.getElementById('subscriptionActionsModal').classList.remove('hidden');
            document.getElementById('subscriptionActionsModal').classList.add('flex');
        }

        function closeSubscriptionModal() {
            document.getElementById('subscriptionActionsModal').classList.add('hidden');
            document.getElementById('subscriptionActionsModal').classList.remove('flex');
        }

        async function saveSubscriptionChanges() {
            const companyId = document.getElementById('manageCompanyId').value;
            const planType = document.getElementById('managePlanType').value;
            const expiryDate = document.getElementById('manageExpiryDate').value;

            if (!expiryDate) {
                alert("ERROR // NULL_PARAMETER: Expiry Override is required.");
                return;
            }

            try {
                const response = await fetch('ajax_update_subscription.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update',
                        companyId: companyId,
                        planType: planType,
                        expiryDate: expiryDate
                    })
                });

                const result = await response.json();
                if (result.success) {
                    closeSubscriptionModal();
                    location.reload();
                } else {
                    alert("SYSTEM_ERROR: " + result.message);
                }
            } catch (error) {
                alert("CRITICAL_FAILURE: Unable to establish connection to logic core.");
            }
        }

        async function toggleSubscriptionStatus() {
            const companyId = document.getElementById('manageCompanyId').value;
            const currentStatus = document.getElementById('manageCurrentStatus').value;

            const isCancelled = (currentStatus && (currentStatus.toLowerCase() === 'cancelled' || currentStatus.toLowerCase() === 'inactive'));

            const actionType = isCancelled ? 'activate' : 'cancel';
            const confirmMsg = isCancelled
                ? "CONFIRM_ACTION // \n\nAre you sure you want to REACTIVATE the subscription for ID: " + companyId + "?"
                : "WARNING_CRITICAL_ACTION // \n\nAre you sure you want to TERMINATE the subscription for ID: " + companyId + "? This action overrides all protocols.";

            if (!confirm(confirmMsg)) {
                return;
            }

            try {
                const response = await fetch('ajax_update_subscription.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: actionType,
                        companyId: companyId
                    })
                });

                const result = await response.json();
                if (result.success) {
                    closeSubscriptionModal();
                    location.reload();
                } else {
                    alert("SYSTEM_ERROR: " + result.message);
                }
            } catch (error) {
                alert("CRITICAL_FAILURE: Unable to establish connection to logic core.");
            }
        }

        async function downloadPDF(invoiceId) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Industrial/Premium Font Config (Standard Fonts)
            doc.setFont("helvetica", "bold");

            // Header - Logo Area
            doc.setFillColor(20, 20, 20); // Dark Background
            doc.rect(0, 0, 210, 40, "F");

            doc.setTextColor(255, 0, 0); // Red Accent
            doc.setFontSize(22);
            doc.text("7 DREAM", 20, 25);

            doc.setTextColor(255, 255, 255);
            doc.setFontSize(10);
            doc.text("SUBSCRIPTION_MODULE // SYSTEM", 20, 32);

            // Invoice Title
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(18);
            doc.text("SUBSCRIPTION INVOICE", 20, 60);

            doc.setDrawColor(255, 0, 0);
            doc.setLineWidth(1);
            doc.line(20, 65, 190, 65);

            // Invoice Details
            doc.setFontSize(12);
            doc.setFont("helvetica", "normal");

            let y = 80;
            const details = [
                { label: "SUBSCRIPTION REF", value: invoiceId },
                { label: "DATE ISSUED", value: new Date().toLocaleDateString() },
                { label: "CYCLE", value: "MONTHLY RECURRING" },
                { label: "CLIENT", value: "Registered Partner" }
            ];

            details.forEach(detail => {
                doc.setFont("helvetica", "bold");
                doc.text(`${detail.label}:`, 20, y);
                doc.setFont("helvetica", "normal");
                doc.text(detail.value, 70, y);
                y += 10;
            });

            // Amount Section
            y += 10;
            doc.setFillColor(240, 240, 240);
            doc.rect(20, y, 170, 30, "F");

            doc.setFont("helvetica", "bold");
            doc.setFontSize(14);
            doc.text("AMOUNT DUE", 30, y + 20);

            doc.setTextColor(255, 0, 0);
            doc.setFontSize(16);
            doc.text("$1,250.00", 140, y + 20);

            // Footer
            doc.setTextColor(150, 150, 150);
            doc.setFontSize(8);
            doc.text("CONFIDENTIAL FINANCIAL RECORD // GENERATED BY HRMS PRO", 20, 280);

            doc.save(`${invoiceId}_Sub.pdf`);
        }

        function downloadInvoice(id) {
            downloadPDF(id);
        }
    </script>
</body>

</html>