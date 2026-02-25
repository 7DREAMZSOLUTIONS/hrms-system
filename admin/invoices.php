<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

require_once '../db_connect_mongo.php';

// Fetch Invoices (using transaction_history and joining companies for address/gst)
$invoices = [];
try {
    $pipeline = [
        [
            '$lookup' => [
                'from' => 'companies',
                'localField' => 'companyId',
                'foreignField' => 'companyId',
                'as' => 'companyDetails'
            ]
        ],
        [
            '$unwind' => [
                'path' => '$companyDetails',
                'preserveNullAndEmptyArrays' => true
            ]
        ],
        [
            '$addFields' => [
                'company_address' => '$companyDetails.address',
                'company_gst' => '$companyDetails.gstNumber',
                'customer_state' => '$companyDetails.state'
            ]
        ],
        [
            '$project' => [
                'companyDetails' => 0 // Hide joined raw array
            ]
        ],
        [
            '$sort' => ['_id' => -1]
        ]
    ];

    $command = new MongoDB\Driver\Command([
        'aggregate' => 'transaction_history',
        'pipeline' => $pipeline,
        'cursor' => new stdClass()
    ]);

    $cursor = $mongoManager->executeCommand($mongodb_name, $command);

    foreach ($cursor as $doc) {
        $invoices[] = $doc;
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
    <title>Invoice Management | HRMS Pro</title>
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

        .status-overdue {
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

    <?php include 'slidebar.php'; ?>

    <main class="flex-1 p-6 md:p-12">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12">
            <div>
                <h1 class="glitch text-3xl font-black italic uppercase">Invoice_Console</h1>
                <p class="text-gray-600 text-[10px] tracking-[4px] mt-1">BILLING_RECORDS // ACCESS_MV</p>
            </div>
            <button onclick="generateInvoice()" class="btn-action mt-6 md:mt-0 w-full md:w-auto">
                <i class="fas fa-file-invoice mr-2"></i> GENERATE NEW INVOICE
            </button>
        </header>

        <div class="overflow-x-auto">
            <table class="industrial-table">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Company Name</th>
                        <th>Billing Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="invoiceTableBody">
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="text-align: center;">
                                <div class="flex flex-col items-center justify-center p-8 text-gray-400 font-mono w-full">
                                    <i class="fas fa-box-open opacity-50 text-4xl mb-4 text-red-600 block"></i>
                                    <span>NO_INVOICE_DATA_FOUND</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv):
                            $invoiceId = htmlspecialchars($inv->invoice_number ?? 'N/A');
                            $companyName = htmlspecialchars($inv->company_name ?? 'Unknown Company');
                            $companyId = htmlspecialchars($inv->companyId ?? 'N/A');

                            $billingDate = $inv->payment_date ?? 'N/A';
                            $amount = isset($inv->amount) ? number_format((float) $inv->amount, 2) : '0.00';

                            $statusRaw = $inv->status ?? 'Unknown';
                            $statusVisual = 'status-paid';

                            if (strtolower($statusRaw) === 'pending') {
                                $statusVisual = 'status-pending';
                            } else if (strtolower($statusRaw) === 'failed' || strtolower($statusRaw) === 'error') {
                                $statusVisual = 'status-overdue'; // using overdue for failed/error status
                            } else if (strtolower($statusRaw) !== 'success' && strtolower($statusRaw) !== 'paid') {
                                $statusVisual = 'status-pending';
                            }

                            $displayText = strtoupper(str_replace('Success', 'PAID', $statusRaw));

                            $baseAmountNum = (float) ($inv->base_amount ?? 0);
                            $gstAmountNum = (float) ($inv->gst_amount ?? 0);
                            $totalAmountNum = (float) ($inv->amount ?? 0);

                            $baseAmount = number_format($baseAmountNum, 2, '.', '');
                            $gstAmount = number_format($gstAmountNum, 2, '.', '');
                            $cgstAmount = number_format($gstAmountNum / 2, 2, '.', '');
                            $sgstAmount = number_format($gstAmountNum / 2, 2, '.', '');
                            $amount = number_format($totalAmountNum, 2, '.', '');

                            $paymentId = htmlspecialchars($inv->payment_id ?? 'N/A');
                            $planType = htmlspecialchars($inv->plan_type ?? 'Enterprise');

                            // Get these if available, otherwise default
                            $companyAddr = htmlspecialchars($inv->company_address ?? '');
                            $companyGst = htmlspecialchars($inv->company_gst ?? 'N/A');
                            $customerState = htmlspecialchars($inv->customer_state ?? 'N/A');
                            ?>
                            <tr>
                                <td data-label="Invoice ID" class="font-bold text-red-600 font-mono"><?php echo $invoiceId; ?>
                                </td>
                                <td data-label="Company Name">
                                    <div class="font-bold text-white"><?php echo $companyName; ?></div>
                                    <div class="text-[9px] text-gray-500 uppercase mt-1">ID: <?php echo $companyId; ?></div>
                                </td>
                                <td data-label="Billing Date">
                                    <div class="text-xs"><?php echo $billingDate; ?></div>
                                    <div class="text-[9px] text-gray-600 uppercase mt-1 italic">
                                        <?php echo ($statusVisual === 'status-paid') ? 'Generated' : 'Due/Pending'; ?>
                                    </div>
                                </td>
                                <td data-label="Amount">
                                    <div
                                        class="text-sm font-black <?php echo ($statusVisual === 'status-paid') ? 'text-white' : 'text-red-600'; ?>">
                                        ₹<?php echo $amount; ?></div>
                                    <div class="text-[9px] text-gray-600 italic mt-1">Inc. 18% GST</div>
                                </td>
                                <td data-label="Status">
                                    <span class="status-pill <?php echo $statusVisual; ?>"><?php echo $displayText; ?></span>
                                </td>
                                <td data-label="Actions">
                                    <button
                                        onclick="viewInvoice('<?php echo $invoiceId; ?>', '<?php echo $companyName; ?>', '<?php echo $amount; ?>', '<?php echo $baseAmount; ?>', '<?php echo $gstAmount; ?>', '<?php echo $statusRaw; ?>', '<?php echo $paymentId; ?>', '<?php echo $billingDate; ?>')"
                                        class="text-gray-600 hover:text-white transition mr-4" title="View"><i
                                            class="fas fa-eye"></i></button>
                                    <button onclick="downloadHTMLInvoice({
                                        id: '<?php echo $invoiceId; ?>',
                                        date: '<?php echo $billingDate; ?>',
                                        company: '<?php echo addslashes($companyName); ?>',
                                        gst: '<?php echo addslashes($companyGst); ?>',
                                        address: '<?php echo addslashes($companyAddr); ?>',
                                        state: '<?php echo addslashes($customerState); ?>',
                                        plan: '<?php echo addslashes($planType); ?>',
                                        base: '<?php echo $baseAmount; ?>',
                                        cgst: '<?php echo $cgstAmount; ?>',
                                        sgst: '<?php echo $sgstAmount; ?>',
                                        total: '<?php echo $amount; ?>'
                                    })" class="text-gray-600 hover:text-white transition" title="Download"><i
                                            class="fas fa-download"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- View Invoice / Transaction Details Modal -->
        <div id="viewInvoiceModal"
            class="fixed inset-0 bg-black/90 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
            <div class="bg-[#111] border border-[#333] border-t-4 border-t-red-600 w-full max-w-lg animation-slide-up">
                <div class="p-6 flex justify-between items-center border-b border-gray-800">
                    <h2 class="glitch text-sm text-red-600 uppercase">TRANSACTION_HISTORY</h2>
                    <button onclick="closeViewInvoice()" class="text-gray-600 hover:text-white transition"><i
                            class="fas fa-times"></i></button>
                </div>
                <div class="p-8 space-y-6">
                    <div class="grid grid-cols-2 gap-4 border-b border-[#222] pb-6">
                        <div>
                            <p class="text-[9px] text-gray-500 uppercase tracking-widest mb-1">Company</p>
                            <p class="font-bold text-white text-sm" id="viewInvCompany">-</p>
                        </div>
                        <div>
                            <p class="text-[9px] text-gray-500 uppercase tracking-widest mb-1">Invoice ID</p>
                            <p class="font-bold text-red-600 font-mono text-sm" id="viewInvId">-</p>
                        </div>
                        <div>
                            <p class="text-[9px] text-gray-500 uppercase tracking-widest mb-1">Transaction Date</p>
                            <p class="text-gray-300 text-xs" id="viewInvDate">-</p>
                        </div>
                        <div>
                            <p class="text-[9px] text-gray-500 uppercase tracking-widest mb-1">Payment ID</p>
                            <p class="text-gray-300 text-xs font-mono" id="viewInvPaymentId">-</p>
                        </div>
                    </div>

                    <div class="space-y-3 pt-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-400 uppercase tracking-widest">Base Amount</span>
                            <span class="text-sm font-mono text-white" id="viewInvBase">₹0.00</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-400 uppercase tracking-widest">GST (18%)</span>
                            <span class="text-sm font-mono text-white" id="viewInvGst">₹0.00</span>
                        </div>
                        <div class="border-t border-[#333] my-2 pt-3 flex justify-between items-center">
                            <span class="text-xs font-bold text-red-600 uppercase tracking-wider">Total Billed</span>
                            <span class="text-lg font-black font-mono text-white" id="viewInvTotal">₹0.00</span>
                        </div>
                        <div class="flex justify-between items-center mt-4">
                            <span class="text-xs text-gray-400 uppercase tracking-widest">Status</span>
                            <span class="status-pill inline-block mt-0" id="viewInvStatus">UNKNOWN</span>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-[#222]">
                        <button onclick="closeViewInvoice()"
                            class="w-full bg-transparent border-2 border-[#333] text-gray-400 font-['Orbitron'] py-3 text-xs tracking-widest font-bold hover:bg-[#222] hover:text-white transition">
                            <i class="fas fa-times mr-2"></i>CLOSE VIEWER
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('backdrop').classList.toggle('open');
        }

        function generateInvoice() {
            alert("INITIALIZING_INVOICE_GENERATOR...\n\nCompiling billing data and hardware logs.");
        }

        function viewInvoice(id, company, amount, base, gst, status, paymentId, date) {
            document.getElementById('viewInvId').textContent = id;
            document.getElementById('viewInvCompany').textContent = company;
            document.getElementById('viewInvDate').textContent = date;
            document.getElementById('viewInvPaymentId').textContent = paymentId;
            document.getElementById('viewInvBase').textContent = "₹" + base;
            document.getElementById('viewInvGst').textContent = "₹" + gst;
            document.getElementById('viewInvTotal').textContent = "₹" + amount;

            const statusEl = document.getElementById('viewInvStatus');
            statusEl.textContent = String(status).toUpperCase();

            // Apply appropriate status coloring class
            statusEl.className = 'status-pill inline-block mt-0 ';
            if (status.toLowerCase() === 'success' || status.toLowerCase() === 'paid') {
                statusEl.className += 'status-paid';
            } else if (status.toLowerCase() === 'pending') {
                statusEl.className += 'status-pending';
            } else {
                statusEl.className += 'status-overdue';
            }

            document.getElementById('viewInvoiceModal').classList.remove('hidden');
            document.getElementById('viewInvoiceModal').classList.add('flex');
        }

        function closeViewInvoice() {
            document.getElementById('viewInvoiceModal').classList.add('hidden');
            document.getElementById('viewInvoiceModal').classList.remove('flex');
        }

        // Helper to convert numbers to words (e.g. 1500 to "One Thousand Five Hundred")
        function numberToWords(num) {
            const a = ['', 'One ', 'Two ', 'Three ', 'Four ', 'Five ', 'Six ', 'Seven ', 'Eight ', 'Nine ', 'Ten ', 'Eleven ', 'Twelve ', 'Thirteen ', 'Fourteen ', 'Fifteen ', 'Sixteen ', 'Seventeen ', 'Eighteen ', 'Nineteen '];
            const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            if ((num = num.toString()).length > 9) return 'overflow';
            n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
            if (!n) return;
            let str = '';
            str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + 'Crore ' : '';
            str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + 'Lakh ' : '';
            str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + 'Thousand ' : '';
            str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + ' ' + a[n[4][1]]) + 'Hundred ' : '';
            str += (n[5] != 0) ? ((str != '') ? 'and ' : '') + (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) : '';
            return str.trim() ? str.trim() + ' Rupees Only' : 'Zero Rupees Only';
        }

        async function downloadHTMLInvoice(data) {
            try {
                // Fetch template
                const response = await fetch('../invoice/invoice.html');
                let htmlTemplate = await response.text();

                // Convert amount to words 
                const amountInWords = numberToWords(Math.round(parseFloat(data.total)));

                // Replace placeholders
                htmlTemplate = htmlTemplate
                    .replace(/{{invoice_number}}/g, data.id)
                    .replace(/{{date}}/g, data.date)
                    .replace(/{{company_name}}/g, data.company || 'Unknown')
                    .replace(/{{company_gst}}/g, data.gst || 'N/A')
                    .replace(/{{company_address}}/g, data.address || 'Address not provided')
                    .replace(/{{customer_state}}/g, data.state || 'N/A')
                    .replace(/{{plan_type}}/g, data.plan)
                    .replace(/{{base_amount}}/g, data.base)
                    .replace(/{{cgst_amount}}/g, data.cgst)
                    .replace(/{{sgst_amount}}/g, data.sgst)
                    .replace(/{{total_amount}}/g, data.total)
                    .replace(/{{amount_in_words}}/g, amountInWords)
                    // Fix image path for correct render
                    .replace(/img\/logo_white\.png/g, '../invoice/img/logo_white.png');

                // Generate PDF using html2pdf
                const opt = {
                    margin: 0,
                    filename: `${data.id}_Invoice.pdf`,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true },
                    jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
                };

                html2pdf().set(opt).from(htmlTemplate).save();

            } catch (err) {
                alert("SYSTEM_ERROR: Failed to generate PDF from template.");
                console.error(err);
            }
        }
    </script>
</body>

</html>