<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

require_once '../db_connect_mongo.php';

// Fetch Transaction History
$transactions = [];
try {
    $response = callApi('/transactions', 'GET');
    if (!empty($response['success']) && !empty($response['data'])) {
        $transactions = $response['data'];
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
    <title>Transaction History | HRMS Pro</title>
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

        .status-paid {
            background: rgba(0, 255, 0, 0.1);
            color: #00FF00;
            border: 1px solid #00FF00;
        }

        .status-pending {
            background: rgba(255, 255, 0, 0.1);
            color: #FFFF00;
            border: 1px solid #FFFF00;
        }

        .status-failed {
            background: rgba(255, 0, 0, 0.1);
            color: #FF0000;
            border: 1px solid #FF0000;
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

    <div id="backdrop" class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <header class="mobile-header">
        <div class="glitch text-lg font-black uppercase tracking-tighter">MV_<span class="text-red-600">PRO</span></div>
        <button onclick="toggleSidebar()" class="text-white text-xl p-2"><i class="fas fa-bars"></i></button>
    </header>

    <aside id="sidebar" class="command-bar flex flex-col">
        <div class="h-20 flex items-center justify-center border-b border-gray-800">
            <div class="w-10 h-10 bg-red-600 flex items-center justify-center font-black">MV</div>
        </div>
        <nav class="mt-4 flex-1">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i><span
                    class="nav-text">DASHBOARD</span></a>
            <a href="companies.php" class="nav-item"><i class="fas fa-industry"></i><span
                    class="nav-text">COMPANIES</span></a>
            <a href="subscriptions.php" class="nav-item"><i class="fas fa-bolt"></i><span
                    class="nav-text">SUBSCRIPTION</span></a>
            <a href="transaction_history.php" class="nav-item active"><i class="fas fa-history"></i><span
                    class="nav-text">TRANS.
                    HISTORY</span></a>
            <a href="invoices.php" class="nav-item"><i class="fas fa-file-invoice-dollar"></i><span
                    class="nav-text">INVOICE</span></a>
        </nav>
        <div class="p-6 border-t border-gray-800 text-center">
            <a href="logout.php" class="nav-item"><i class="fas fa-power-off"></i><span
                    class="nav-text">LOGOUT</span></a>
        </div>
    </aside>

    <main class="flex-1 p-6 md:p-12">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12">
            <div>
                <h1 class="glitch text-3xl font-black italic uppercase">Transaction_Ledger</h1>
                <p class="text-gray-600 text-[10px] tracking-[4px] mt-1">FINANCIAL_RECORDS // ACCESS_MV</p>
            </div>
            <button onclick="exportLedger()" class="btn-action mt-6 md:mt-0 w-full md:w-auto">
                <i class="fas fa-download mr-2"></i> EXPORT ALL RECORDS
            </button>
        </header>

        <div class="overflow-x-auto">
            <table class="industrial-table">
                <thead>
                    <tr>
                        <th>Company Detail</th>
                        <th>Invoice Amount</th>
                        <th>Sub. Amount</th>
                        <th>GST Amount</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="transactionTableBody">
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" class="text-center" style="text-align: center;">
                                <div class="flex flex-col items-center justify-center p-8 text-gray-400 font-mono w-full">
                                    <i class="fas fa-box-open opacity-50 text-4xl mb-4 text-red-600 block"></i>
                                    <span>NO_TRANSACTION_RECORDS_FOUND</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $txn):
                            $companyName = htmlspecialchars($txn['company_name'] ?? 'Unknown Company');
                            $invoiceNum = htmlspecialchars($txn['invoice_number'] ?? 'N/A');

                            $totalAmount = isset($txn['amount']) ? number_format((float) $txn['amount'], 2) : '0.00';
                            $baseAmount = isset($txn['base_amount']) ? number_format((float) $txn['base_amount'], 2) : '0.00';
                            $gstAmount = isset($txn['gst_amount']) ? number_format((float) $txn['gst_amount'], 2) : '0.00';

                            $statusRaw = $txn['status'] ?? 'Unknown';
                            $statusVisual = 'status-paid';

                            if (strtolower($statusRaw) === 'pending') {
                                $statusVisual = 'status-pending';
                            } else if (strtolower($statusRaw) === 'failed' || strtolower($statusRaw) === 'error') {
                                $statusVisual = 'status-failed';
                            } else if (strtolower($statusRaw) !== 'success' && strtolower($statusRaw) !== 'paid') {
                                $statusVisual = 'status-pending'; // default for other unrecognized statuses
                            }

                            // Adjust display text for status pill
                            $displayText = strtoupper(str_replace('Success', 'PAID', $statusRaw));
                            ?>
                            <tr>
                                <td data-label="Company Detail">
                                    <div class="font-bold text-white">
                                        <?php echo $companyName; ?>
                                    </div>
                                    <div class="text-[9px] text-gray-500 uppercase mt-1">REF:
                                        <?php echo $invoiceNum; ?>
                                    </div>
                                </td>
                                <td data-label="Invoice Amount">
                                    <div class="text-xs">₹
                                        <?php echo $totalAmount; ?>
                                    </div>
                                </td>
                                <td data-label="Sub. Amount">
                                    <div class="text-xs">₹
                                        <?php echo $baseAmount; ?>
                                    </div>
                                </td>
                                <td data-label="GST Amount">
                                    <div class="text-xs">₹
                                        <?php echo $gstAmount; ?>
                                    </div>
                                    <div class="text-[8px] text-gray-600">18% GST</div>
                                </td>
                                <td data-label="Total Amount">
                                    <div
                                        class="text-sm font-black <?php echo ($statusVisual === 'status-paid') ? 'text-green-500' : 'text-red-600'; ?>">
                                        ₹
                                        <?php echo $totalAmount; ?>
                                    </div>
                                </td>
                                <td data-label="Status">
                                    <span class="status-pill <?php echo $statusVisual; ?>">
                                        <?php echo $displayText; ?>
                                    </span>
                                </td>
                                <td data-label="Actions">
                                    <button onclick="downloadRecord('<?php echo $invoiceNum; ?>')"
                                        class="text-gray-600 hover:text-white transition"><i
                                            class="fas fa-download"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('backdrop').classList.toggle('open');
        }

        function exportLedger() {
            alert("EXPORTING_GLOBAL_LEDGER...\n\nProcessing transaction packets for CSV/XLS generation.");
        }

        function downloadRecord(ref) {
            alert("DOWNLOADING_RECORD // REF: " + ref + "\n\nFetching secure financial manifest.");
        }
    </script>
</body>

</html>