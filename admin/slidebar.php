<div id="backdrop" class="sidebar-backdrop" onclick="toggleSidebar()"></div>

<header class="mobile-header">
    <div class="glitch text-lg font-black uppercase tracking-tighter">7HRMS<span class="text-red-600">PRO</span></div>
    <button onclick="toggleSidebar()" class="text-white text-xl p-2"><i class="fas fa-bars"></i></button>
</header>

<aside id="sidebar" class="command-bar flex flex-col">
    <div class="h-20 flex items-center justify-center border-b border-gray-800">
        <div class="w-10 h-10 bg-red-600 flex items-center justify-center font-black">7PRO</div>
    </div>
    <nav class="mt-4 flex-1">
        <a href="dashboard.php" class="nav-item active"><i class="fas fa-th-large"></i><span
                class="nav-text">DASHBOARD</span></a>
        <a href="companies.php" class="nav-item"><i class="fas fa-industry"></i><span
                class="nav-text">COMPANIES</span></a>
        <a href="subscriptions.php" class="nav-item"><i class="fas fa-bolt"></i><span
                class="nav-text">SUBSCRIPTION</span></a>
        <a href="transaction_history.php" class="nav-item"><i class="fas fa-history"></i><span class="nav-text">TRANS.
                HISTORY</span></a>
        <a href="invoices.php" class="nav-item"><i class="fas fa-file-invoice-dollar"></i><span
                class="nav-text">INVOICE</span></a>
    </nav>
    <div class="p-6 border-t border-gray-800 text-center">
        <a href="logout.php" class="nav-item"><i class="fas fa-power-off"></i><span class="nav-text">LOGOUT</span></a>
    </div>
</aside>