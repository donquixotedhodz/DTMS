<?php
// =========================
// Active Page Detection
// =========================
$currentPage  = basename($_SERVER['PHP_SELF']);
$currentQuery = $_GET['type'] ?? '';

// =========================
// Helper Functions
// =========================
function isActive($page, $query = '') {
    global $currentPage, $currentQuery;
    return $page === $currentPage && ($query === '' || $query === $currentQuery);
}

function isSectionActive($keyword) {
    global $currentQuery;
    return strpos($currentQuery, $keyword) !== false;
}

// =========================
// Unified Styles
// =========================
$activeBg     = 'style="background-color:#009246;"';
$activeClass = 'text-white';
$inactive    = 'text-gray-700 hover:bg-gray-100';
$iconColor   = 'style="color:#009246;"';
?>

<!-- Sidebar -->
<div id="sidebar" class="sidebar fixed left-0 top-16 h-screen w-64 bg-gradient-to-b from-white to-gray-50 shadow-xl lg:relative z-40">
    <div class="h-full flex flex-col">

        <!-- Header -->
        <div class="px-4 py-3 border-b bg-white">
            <h2 class="text-base font-bold text-[#009246]">Navigation</h2>
            <p class="text-xs text-gray-500">Document Management</p>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-2 py-3 overflow-hidden">

            <!-- Dashboard -->
            <a href="dashboard.php"
               class="nav-item flex items-center px-3 py-2 rounded-lg transition-all
               <?= isActive('dashboard.php') ? $activeClass : $inactive ?>"
               <?= isActive('dashboard.php') ? $activeBg : '' ?>>
                <i class="fas fa-home w-4 text-sm" <?= !isActive('dashboard.php') ? $iconColor : '' ?>></i>
                <span class="ml-2 text-sm font-medium">Dashboard</span>
            </a>

            <!-- Documents -->
            <p class="px-3 pt-4 pb-1 text-xs font-bold text-gray-400 uppercase">Documents</p>

            <!-- Incoming -->
            <button onclick="toggle('incoming')"
                class="w-full flex justify-between items-center px-3 py-2 rounded-lg transition-all
                <?= isSectionActive('incoming') ? $activeClass : $inactive ?>"
                <?= isSectionActive('incoming') ? $activeBg : '' ?>>
                <div class="flex items-center">
                    <i class="fas fa-arrow-down w-4 text-sm" <?= !isSectionActive('incoming') ? $iconColor : '' ?>></i>
                    <span class="ml-2 text-sm font-medium">Incoming</span>
                </div>
                <i id="incomingChevron" class="fas fa-chevron-down text-xs transition-transform"></i>
            </button>

            <div id="incomingMenu" class="<?= isSectionActive('incoming') ? '' : 'hidden' ?> ml-3 bg-gray-50 rounded-lg mt-1 p-1">
                <?php foreach (['internal','external'] as $t): ?>
                    <a href="upload_document.php?type=incoming-<?= $t ?>"
                       class="flex items-center px-3 py-1.5 rounded-lg text-xs transition-all
                       <?= isActive('upload_document.php',"incoming-$t") ? $activeClass : 'text-gray-600 hover:bg-gray-100' ?>"
                       <?= isActive('upload_document.php',"incoming-$t") ? $activeBg : '' ?>>
                        <i class="fas fa-dot-circle w-2" <?= !isActive('upload_document.php',"incoming-$t") ? $iconColor : '' ?>></i>
                        <span class="ml-2"><?= ucfirst($t) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Outgoing -->
            <button onclick="toggle('outgoing')"
                class="w-full flex justify-between items-center px-3 py-2 mt-1 rounded-lg transition-all
                <?= isSectionActive('outgoing') ? $activeClass : $inactive ?>"
                <?= isSectionActive('outgoing') ? $activeBg : '' ?>>
                <div class="flex items-center">
                    <i class="fas fa-arrow-up w-4 text-sm" <?= !isSectionActive('outgoing') ? $iconColor : '' ?>></i>
                    <span class="ml-2 text-sm font-medium">Outgoing</span>
                </div>
                <i id="outgoingChevron" class="fas fa-chevron-down text-xs transition-transform"></i>
            </button>

            <div id="outgoingMenu" class="<?= isSectionActive('outgoing') ? '' : 'hidden' ?> ml-3 bg-gray-50 rounded-lg mt-1 p-1">
                <?php foreach (['internal','external'] as $t): ?>
                    <a href="upload_document.php?type=outgoing-<?= $t ?>"
                       class="flex items-center px-3 py-1.5 rounded-lg text-xs transition-all
                       <?= isActive('upload_document.php',"outgoing-$t") ? $activeClass : 'text-gray-600 hover:bg-gray-100' ?>"
                       <?= isActive('upload_document.php',"outgoing-$t") ? $activeBg : '' ?>>
                        <i class="fas fa-dot-circle w-2" <?= !isActive('upload_document.php',"outgoing-$t") ? $iconColor : '' ?>></i>
                        <span class="ml-2"><?= ucfirst($t) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Archive -->
            <a href="archive.php"
               class="flex items-center px-3 py-2 mt-2 rounded-lg transition-all
               <?= isActive('archive.php') ? $activeClass : $inactive ?>"
               <?= isActive('archive.php') ? $activeBg : '' ?>>
                <i class="fas fa-archive w-4 text-sm" <?= !isActive('archive.php') ? $iconColor : '' ?>></i>
                <span class="ml-2 text-sm font-medium">Archive</span>
            </a>

            <!-- Settings -->
            <p class="px-3 pt-4 pb-1 text-xs font-bold text-gray-400 uppercase">Settings</p>

            <a href="user_management.php"
               class="flex items-center px-3 py-2 rounded-lg transition-all
               <?= isActive('user_management.php') ? $activeClass : $inactive ?>"
               <?= isActive('user_management.php') ? $activeBg : '' ?>>
                <i class="fas fa-users w-4 text-sm" <?= !isActive('user_management.php') ? $iconColor : '' ?>></i>
                <span class="ml-2 text-sm font-medium">Users</span>
            </a>

        </nav>
    </div>
</div>

<script>
function toggle(section) {
    const menu = document.getElementById(section + 'Menu');
    const icon = document.getElementById(section + 'Chevron');
    menu.classList.toggle('hidden');
    icon.style.transform = menu.classList.contains('hidden')
        ? 'rotate(0deg)'
        : 'rotate(180deg)';
}
</script>
