<?php
session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

require_once '../db_connect_mongo.php';

$companies = [];

try {

    // Call Node API instead of MongoDB
    $response = callApi('/subscription');

    if (!empty($response['success']) && !empty($response['data'])) {
        $companies = $response['data'];
    }

} catch (Exception $e) {
    $error_msg = "API Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Management | HRMS Pro</title>
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
            z-index: 100;
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
                backdrop-filter: blur(4px);
                z-index: 95;
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

        @media (max-width: 768px) {

            .industrial-table,
            .industrial-table thead,
            .industrial-table tbody,
            .industrial-table th,
            .industrial-table td,
            .industrial-table tr {
                display: block;
            }

            .industrial-table thead {
                display: none;
            }

            .industrial-table tr {
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

        /* Modal Overlay */
        #modalOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 200;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-box {
            background: #111;
            border: 1px solid #333;
            width: 100%;
            max-width: 650px;
            border-top: 4px solid var(--red);
            animation: slideUp 0.4s ease;
            max-height: 90vh;
            overflow-y: auto;
        }

        @media (max-width: 768px) {
            .modal-box {
                max-width: 100% !important;
                height: 100% !important;
                max-height: 100vh !important;
                margin: 0 !important;
                border: none !important;
                border-top: 4px solid var(--red) !important;
                border-radius: 0 !important;
            }

            #modalOverlay {
                padding: 0 !important;
            }

            .step-content {
                padding-bottom: 100px;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .step-bar {
            flex: 1;
            height: 3px;
            background: #333;
            transition: 0.4s;
        }

        .step-bar.active {
            background: var(--red);
            box-shadow: 0 0 10px var(--red);
        }

        input,
        textarea,
        select {
            background: #0A0A0A !important;
            border: 1px solid #333 !important;
            color: white !important;
            padding: 12px !important;
            width: 100%;
            outline: none;
            transition: 0.3s;
        }

        input:focus,
        textarea:focus {
            border-color: var(--red) !important;
            box-shadow: 0 0 8px rgba(255, 0, 0, 0.2);
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

        .btn-secondary {
            color: #666;
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
        }
    </style>
</head>

<body class="flex flex-col md:flex-row">

    <?php require_once "slidebar.php"; ?>


    <main class="flex-1 p-6 md:p-12">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12">
            <div>
                <h1 class="glitch text-3xl font-black italic uppercase">Company_Management</h1>
                <p class="text-gray-600 text-[10px] tracking-[4px] mt-1">SECURE_DATABASE // ACCESS_MV</p>
            </div>
            <button onclick="openModal()" class="btn-action mt-6 md:mt-0 w-full md:w-auto">
                <i class="fas fa-plus mr-2"></i> ADD NEW COMPANY
            </button>
        </header>

        <div class="overflow-x-auto">
            <table class="industrial-table">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Device ID</th>
                        <th>Plan Type</th>
                        <th>Users</th>
                        <th>Amount</th>
                        <th>Next Billing</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="companyTableBody">

                    <?php if (empty($companies)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">No companies found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($companies as $company): ?>
                            <tr>
                                <td data-label="Company Name">
                                    <strong><?php echo htmlspecialchars($company['company_name'] ?? 'N/A'); ?></strong>
                                    <!-- <div style="font-size:0.85rem; color:#888;">ID: <?php echo htmlspecialchars($company['company_id'] ?? ''); ?></div> -->
                                </td>
                                <td data-label="Device ID"><span
                                        class="font-mono text-red-500 border border-red-500/30 px-2 py-1 rounded bg-red-500/10 text-xs"><?php echo htmlspecialchars($company['deviceId'] ?? 'N/A'); ?></span>
                                </td>
                                <td data-label="Plan Type"><?php echo htmlspecialchars($company['plan_type'] ?? 'Starter'); ?>
                                </td>
                                <td data-label="Users"><?php echo htmlspecialchars($company['num_users'] ?? 0); ?></td>
                                <td data-label="Amount">₹ <?php echo htmlspecialchars($company['subscription_amount'] ?? 0); ?>
                                </td>
                                <td data-label="Next Billing">
                                    <?php echo htmlspecialchars($company['next_subscription_date'] ?? 'N/A'); ?>
                                </td>
                                <td data-label="Status">
                                    <?php
                                    $status = $company['status'] ?? 'Inactive';
                                    $statusClass = (strtolower($status) === 'active') ? 'status-active' : 'status-inactive';
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td data-label="Actions">
                                    <button class="text-gray-400 hover:text-white mr-3 transition" title="View"
                                        onclick="viewCompany('<?php echo htmlspecialchars($company['company_id'] ?? ''); ?>')"><i
                                            class="fas fa-eye"></i></button>
                                    <button class="text-gray-400 hover:text-red-500 mr-3 transition" title="Edit"
                                        onclick="editCompany(this.closest('tr'), '<?php echo htmlspecialchars($company['company_id'] ?? ''); ?>')"><i
                                            class="fas fa-edit"></i></button>
                                    <button class="text-gray-400 hover:text-red-700 transition" title="Delete"
                                        onclick="deleteCompany(this.closest('tr'), '<?php echo htmlspecialchars($company['company_id'] ?? ''); ?>')"><i
                                            class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modalOverlay">
        <div class="modal-box shadow-2xl rounded-sm">
            <div class="p-6 flex justify-between items-center border-b border-gray-800">
                <h2 class="glitch text-sm text-red-600 uppercase">Secure_Entry_Protocol</h2>
                <button onclick="closeModal()" class="text-gray-600 hover:text-white transition"><i
                        class="fas fa-times"></i></button>
            </div>

            <div class="p-8">
                <div class="flex gap-2 mb-10">
                    <div id="bar1" class="step-bar active"></div>
                    <div id="bar2" class="step-bar"></div>
                    <div id="bar3" class="step-bar"></div>
                </div>

                <form id="companyForm">
                    <div id="step1" class="step-content space-y-5">
                        <label
                            class="text-[9px] text-gray-600 font-black uppercase tracking-widest block mb-1">01_ORGANIZATION_IDENTITY</label>
                        <input type="text" id="cName" placeholder="COMPANY NAME" required>
                        <input type="text" id="cGst" placeholder="GST NUMBER" required>
                        <textarea id="cAddr" placeholder="FULL OPERATIONAL ADDRESS" rows="3"></textarea>
                    </div>

                    <div id="step2" class="step-content space-y-5 hidden">
                        <label
                            class="text-[9px] text-gray-600 font-black uppercase tracking-widest block mb-1">02_HARDWARE_SUBSCRIPTION</label>
                        <input type="text" id="dId" placeholder="UNIQUE DEVICE ID (SERIAL)">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-[9px] text-gray-500 mb-2 block uppercase">Sub. Expiry Date</label>
                                <input type="date" id="dSubExp">
                            </div>
                            <div>
                                <label class="text-[9px] text-gray-500 mb-2 block uppercase">Plan Type</label>
                                <select id="cPlanType"
                                    class="w-full bg-[#1A1A1A] border-2 border-[#333] p-4 text-xs font-mono text-white focus:outline-none focus:border-red-600 focus:shadow-[0_0_15px_rgba(255,0,0,0.2)] transition uppercase tracking-widest">
                                    <option value="Starter" selected>Starter</option>
                                    <option value="Pro">Pro</option>
                                    <option value="Enterprise">Enterprise</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="step3" class="step-content space-y-5 hidden">
                        <label
                            class="text-[9px] text-gray-600 font-black uppercase tracking-widest block mb-1">03_PERSONNEL_AUTHENTICATION</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" id="eName" placeholder="EMPLOYEE NAME">
                            <input type="text" id="eCode" placeholder="EMPLOYEE CODE">
                            <input type="tel" id="ePhone" placeholder="MOBILE NUMBER">
                            <input type="email" id="eEmail" placeholder="ENTERPRISE EMAIL">
                            <input type="text" id="eCompId" placeholder="COMPANY ID">
                        </div>
                    </div>

                    <div class="flex justify-between items-center mt-12">
                        <button type="button" id="prevBtn" onclick="moveStep(-1)"
                            class="btn-secondary hidden uppercase px-2 hover:text-white transition">Back</button>
                        <button type="button" id="nextBtn" onclick="moveStep(1)" class="btn-action ml-auto">NEXT
                            PHASE</button>
                    </div>
                </form>
            </div>
        </div>
    </div> <!-- END modalOverlay -->

    <div id="successPopup"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[300] hidden items-center justify-center">
        <div class="bg-[#111] border-2 border-green-500 p-8 rounded-sm text-center max-w-md w-full mx-4 shadow-[0_0_30px_rgba(0,255,0,0.2)] transform scale-95 opacity-0 transition-all duration-300"
            id="successPopupContent">
            <div class="w-16 h-16 bg-green-500/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check text-3xl text-green-500"></i>
            </div>
            <h2 class="font-['Orbitron'] text-xl text-green-500 tracking-wider mb-2 uppercase font-black">Success
            </h2>
            <p class="text-gray-400 font-mono text-xs tracking-widest mb-8">COMPANY ADDED SUCCESSFULLY</p>
            <button onclick="closeSuccessPopup()"
                class="bg-green-500 hover:bg-green-600 text-black font-['Orbitron'] font-bold px-8 py-3 text-xs tracking-widest transition-colors duration-300 w-full">CONTINUE</button>
        </div>
    </div>

    <!-- View Company Modal -->
    <div id="viewCompanyModal"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
        <div
            class="bg-[#111] border border-[#333] border-t-4 border-t-red-600 w-full max-w-2xl max-h-[90vh] overflow-y-auto animation-slide-up">
            <div class="p-6 flex justify-between items-center border-b border-gray-800">
                <h2 class="glitch text-sm text-red-600 uppercase">Company_Overview</h2>
                <button onclick="closeViewModal()" class="text-gray-600 hover:text-white transition"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="p-8 space-y-8" id="viewModalContent">
                <!-- Content will be injected via JS -->
                <div class="flex justify-center items-center py-10">
                    <i class="fas fa-spinner fa-spin text-red-600 text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        let editingRow = null;

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('backdrop').classList.toggle('open');
        }

        function openModal(row = null) {
            editingRow = row;
            document.getElementById('modalOverlay').style.display = 'flex';

            if (editingRow) {
                // Populate form with existing data
                const cells = editingRow.cells;
                document.getElementById('cName').value = editingRow.getAttribute('data-name');
                document.getElementById('cGst').value = editingRow.getAttribute('data-gst');
                document.getElementById('cAddr').value = editingRow.getAttribute('data-addr');
                document.getElementById('dId').value = editingRow.getAttribute('data-did');
                document.getElementById('dSubExp').value = editingRow.getAttribute('data-sExp');
                document.getElementById('eName').value = editingRow.getAttribute('data-eName');
                document.getElementById('eCode').value = editingRow.getAttribute('data-eCode');
                document.getElementById('ePhone').value = editingRow.getAttribute('data-ePhone');
                document.getElementById('eEmail').value = editingRow.getAttribute('data-eEmail');
                document.getElementById('eCompId').value = editingRow.getAttribute('data-eCid');
                document.getElementById('eCompId').readOnly = true; // Make Company ID readonly during edit
                document.querySelector('#modalOverlay h2').innerText = 'Modify_Entry_Alpha';
                document.getElementById('nextBtn').innerText = currentStep === 3 ? "SAVE_CHANGES" : "NEXT PHASE";
            } else {
                document.querySelector('#modalOverlay h2').innerText = 'Secure_Entry_Protocol';
                document.getElementById('eCompId').readOnly = false; // Ensure it's editable for new companies
            }
        }

        function closeModal() {
            document.getElementById('modalOverlay').style.display = 'none';
            resetForm();
        }

        function resetForm() {
            currentStep = 1;
            editingRow = null;
            document.getElementById('companyForm').reset();
            const contents = document.querySelectorAll('.step-content');
            const bars = document.querySelectorAll('.step-bar');
            contents.forEach((c, i) => c.classList.toggle('hidden', i !== 0));
            bars.forEach((b, i) => b.classList.toggle('active', i === 0));
            document.getElementById('prevBtn').classList.add('hidden');
            document.getElementById('nextBtn').innerText = "NEXT PHASE";
            document.querySelectorAll('#companyForm input, #companyForm textarea').forEach(el => el.readOnly = false);
        }

        function viewCompany(id) {
            if (!id) {
                alert("Invalid Company ID");
                return;
            }

            document.getElementById('viewCompanyModal').classList.remove('hidden');
            document.getElementById('viewCompanyModal').classList.add('flex');

            const contentDiv = document.getElementById('viewModalContent');
            contentDiv.innerHTML = `
                    <div class="flex justify-center items-center py-10">
                        <i class="fas fa-spinner fa-spin text-red-600 text-3xl"></i>
                    </div>
                `;

            fetch(`ajax_get_company.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const details = data.data;
                        contentDiv.innerHTML = `
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div>
                                        <h3 class="text-[9px] text-gray-600 font-black uppercase tracking-widest mb-3 border-b border-gray-800 pb-2">Organization</h3>
                                        <div class="space-y-4">
                                            <div>
                                                <div class="text-[10px] text-gray-500 uppercase">Name</div>
                                                <div class="font-bold text-lg">${details.companyName}</div>
                                            </div>
                                            <div>
                                                <div class="text-[10px] text-gray-500 uppercase">Company ID</div>
                                                <div class="font-mono text-sm text-red-400">${details.companyId}</div>
                                            </div>
                                            <div>
                                                <div class="text-[10px] text-gray-500 uppercase">GST Number</div>
                                                <div class="text-sm">${details.gstNumber}</div>
                                            </div>
                                            <div>
                                                <div class="text-[10px] text-gray-500 uppercase">Status</div>
                                                <div class="text-sm">${details.status === 'Active' ? '<span class="text-green-500">ACTIVE</span>' : '<span class="text-red-500">INACTIVE</span>'}</div>
                                            </div>
                                            <div>
                                                <div class="text-[10px] text-gray-500 uppercase">Address</div>
                                                <div class="text-sm">${details.address || 'N/A'}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-8">
                                        <div>
                                            <h3 class="text-[9px] text-gray-600 font-black uppercase tracking-widest mb-3 border-b border-gray-800 pb-2">Hardware & Sub.</h3>
                                            <div class="space-y-4">
                                                <div>
                                                    <div class="text-[10px] text-gray-500 uppercase">Device ID</div>
                                                    <div class="font-mono text-sm text-red-400">${details.deviceId}</div>
                                                </div>
                                                <div>
                                                    <div class="text-[10px] text-gray-500 uppercase">Subscription Expiry</div>
                                                    <div class="text-sm">${details.subscriptionExpiry}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <h3 class="text-[9px] text-gray-600 font-black uppercase tracking-widest mb-3 border-b border-gray-800 pb-2">Admin Contact</h3>
                                            <div class="space-y-4">
                                                <div>
                                                    <div class="text-[10px] text-gray-500 uppercase">Admin Name</div>
                                                    <div class="text-sm">${details.adminName}</div>
                                                </div>
                                                <div>
                                                    <div class="text-[10px] text-gray-500 uppercase">Email</div>
                                                    <div class="text-sm text-blue-400">${details.adminEmail}</div>
                                                </div>
                                                <div>
                                                    <div class="text-[10px] text-gray-500 uppercase">Phone</div>
                                                    <div class="text-sm">${details.adminPhone}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                    } else {
                        contentDiv.innerHTML = `<div class="text-red-500 text-center py-10">Error: ${data.message}</div>`;
                    }
                })
                .catch(err => {
                    contentDiv.innerHTML = `<div class="text-red-500 text-center py-10">Failed to fetch data: ${err.message}</div>`;
                });
        }

        function closeViewModal() {
            document.getElementById('viewCompanyModal').classList.remove('flex');
            document.getElementById('viewCompanyModal').classList.add('hidden');
        }

        function editCompany(row, id) {
            if (!id) {
                alert("Invalid Company ID");
                return;
            }

            // If editing row doesn't have data, fetch from backend
            fetch(`ajax_get_company.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const details = data.data;

                        // Populate the attributes on the row so openModal works properly
                        row.setAttribute('data-name', details.companyName || '');
                        row.setAttribute('data-gst', details.gstNumber !== 'N/A' ? details.gstNumber : '');
                        row.setAttribute('data-addr', details.address !== 'N/A' ? details.address : '');
                        row.setAttribute('data-did', details.deviceId !== 'N/A' ? details.deviceId : '');
                        row.setAttribute('data-sExp', details.subscriptionExpiry !== 'N/A' ? details.subscriptionExpiry : '');
                        row.setAttribute('data-eName', details.adminName !== 'N/A' ? details.adminName : '');
                        row.setAttribute('data-eCode', details.empCode !== 'N/A' ? details.empCode : '');
                        row.setAttribute('data-ePhone', details.adminPhone !== 'N/A' ? details.adminPhone : '');
                        row.setAttribute('data-eEmail', details.adminEmail !== 'N/A' ? details.adminEmail : '');
                        row.setAttribute('data-eCid', details.companyId || '');

                        openModal(row);
                    } else {
                        alert("Failed to fetch company details: " + data.message);
                    }
                })
                .catch(err => {
                    alert("Error fetching details: " + err.message);
                });
        }

        function deleteCompany(row, id) {
            if (confirm("PROTOCOL_WARNING: Are you sure you want to permanently delete company ID: " + (id || 'N/A') + "? This will erase all related admins, employees, devices, subscriptions, and transactions.")) {

                fetch('ajax_delete_company.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ eCid: id })
                })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            row.remove();
                            alert("SYSTEM_MESSAGE: Company and all associated records permanently deleted.");
                        } else {
                            alert("PROTOCOL ERROR: " + (result.message || "Failed to delete company"));
                        }
                    })
                    .catch(err => {
                        alert("PROTOCOL ERROR: " + err.message);
                    });
            }
        }

        function moveStep(n) {
            const contents = document.querySelectorAll('.step-content');
            const bars = document.querySelectorAll('.step-bar');

            if (n > 0) {
                const currentInputs = contents[currentStep - 1].querySelectorAll('[required]');
                for (let input of currentInputs) {
                    if (!input.value.trim()) {
                        alert("PROTOCOL ERROR: MISSING_REQUIRED_FIELDS");
                        return;
                    }
                }
            }

            contents[currentStep - 1].classList.add('hidden');
            currentStep += n;

            if (currentStep > 3) {
                commitData();
                return;
            }

            contents[currentStep - 1].classList.remove('hidden');
            document.getElementById('prevBtn').classList.toggle('hidden', currentStep === 1);
            document.getElementById('nextBtn').innerText = currentStep === 3 ? (editingRow ? "SAVE_CHANGES" : "COMMIT_DATA") : "NEXT PHASE";
            bars.forEach((b, i) => b.classList.toggle('active', i < currentStep));
        }

        function commitData() {
            const data = {
                name: document.getElementById('cName').value,
                gst: document.getElementById('cGst').value,
                addr: document.getElementById('cAddr').value,
                did: document.getElementById('dId').value,
                sExp: document.getElementById('dSubExp').value,
                planType: document.getElementById('cPlanType').value,
                eName: document.getElementById('eName').value,
                eCode: document.getElementById('eCode').value,
                ePhone: document.getElementById('ePhone').value,
                eEmail: document.getElementById('eEmail').value,
                eCid: document.getElementById('eCompId').value
            };

            if (editingRow) {
                const nextBtn = document.getElementById('nextBtn');
                const oldText = nextBtn.innerText;
                nextBtn.innerText = "SAVING...";
                nextBtn.disabled = true;

                fetch('ajax_update_company.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            updateRow(editingRow, data);
                            alert("SYSTEM_MESSAGE: DATA_MODIFIED_SUCCESSFULLY");
                            closeModal();
                        } else {
                            alert("PROTOCOL ERROR: " + (result.message || "Unknown error"));
                        }
                    })
                    .catch(err => {
                        alert("PROTOCOL ERROR: " + err.message);
                    })
                    .finally(() => {
                        nextBtn.innerText = oldText;
                        nextBtn.disabled = false;
                    });
            } else {
                const nextBtn = document.getElementById('nextBtn');
                const oldText = nextBtn.innerText;
                nextBtn.innerText = "PROCESSING...";
                nextBtn.disabled = true;

                fetch('ajax_add_company.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            showSuccessPopup();
                        } else {
                            alert("PROTOCOL ERROR: " + (result.message || "Unknown error"));
                            nextBtn.innerText = oldText;
                            nextBtn.disabled = false;
                        }
                    })
                    .catch(err => {
                        alert("PROTOCOL ERROR: " + err.message);
                        nextBtn.innerText = oldText;
                        nextBtn.disabled = false;
                    });
            }
        }

        function updateRow(row, data) {
            // Preserve existing values that aren't in the form
            // cells index: 0=Name, 1=Device, 2=Plan, 3=Users, 4=Amount, 5=Billing, 6=Status
            const oldUsers = row.cells[3] ? row.cells[3].innerText : '0';
            const oldAmount = row.cells[4] ? row.cells[4].innerText : '₹ 0.00';
            const oldStatus = row.cells[6] ? row.cells[6].innerHTML : '<span class="status-badge status-active">Active</span>';

            // Store data in attributes for easy retrieval during edit
            row.setAttribute('data-name', data.name);
            row.setAttribute('data-gst', data.gst);
            row.setAttribute('data-addr', data.addr);
            row.setAttribute('data-did', data.did);
            row.setAttribute('data-sExp', data.sExp);
            row.setAttribute('data-eName', data.eName);
            row.setAttribute('data-eCode', data.eCode);
            row.setAttribute('data-ePhone', data.ePhone);
            row.setAttribute('data-eEmail', data.eEmail);
            row.setAttribute('data-eCid', data.eCid);

            row.innerHTML = `
                <td data-label="Company Name">
                    <strong>${data.name || 'N/A'}</strong>
                </td>
                <td data-label="Device ID"><span class="font-mono text-red-500 border border-red-500/30 px-2 py-1 rounded bg-red-500/10 text-xs">${data.did || 'N/A'}</span></td>
                <td data-label="Plan Type">${data.planType || 'Starter'}</td>
                <td data-label="Users">${oldUsers}</td>
                <td data-label="Amount">${oldAmount}</td>
                <td data-label="Next Billing">${data.sExp || 'N/A'}</td>
                <td data-label="Status">${oldStatus}</td>
                <td data-label="Actions">
                    <button class="text-gray-400 hover:text-white mr-3 transition" title="View" onclick="viewCompany('${data.eCid}')"><i class="fas fa-eye"></i></button>
                    <button class="text-gray-400 hover:text-red-500 mr-3 transition" title="Edit" onclick="editCompany(this.closest('tr'), '${data.eCid}')"><i class="fas fa-edit"></i></button>
                    <button class="text-gray-400 hover:text-red-700 transition" title="Delete" onclick="deleteCompany(this.closest('tr'), '${data.eCid}')"><i class="fas fa-trash"></i></button>
                </td>
            `;
        }
        function showSuccessPopup() {
            closeModal();
            const popup = document.getElementById('successPopup');
            const content = document.getElementById('successPopupContent');

            popup.classList.remove('hidden');
            popup.classList.add('flex');

            // Slight delay to allow display:flex to apply before animating opacity/transform
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeSuccessPopup() {
            const popup = document.getElementById('successPopup');
            const content = document.getElementById('successPopupContent');

            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                popup.classList.remove('flex');
                popup.classList.add('hidden');
                location.reload();
            }, 300);
        }
    </script>
</body>

</html>