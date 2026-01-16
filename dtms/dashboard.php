<?php
require 'session_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEA DTMS - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            transition: transform 0.3s ease;
        }
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        .nav-item {
            transition: all 0.3s ease;
        }
        .nav-item:hover {
            background-color: rgba(0, 146, 70, 0.1);
            transform: translateX(4px);
        }
        .nav-item.active {
            background-color: rgba(0, 146, 70, 0.2);
            border-left: 4px solid #009246;
        }
    </style>
</head>
<body class="bg-gray-100">
    
    <!-- Top Navigation -->
    <nav class="bg-white shadow-lg fixed w-full top-0 z-50" style="background-color: #009246;">
        <div class="px-6 py-4 flex justify-between items-center text-white">
            <div class="flex items-center space-x-4">
                <button id="sidebarToggle" class="lg:hidden text-2xl focus:outline-none">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="flex items-center space-x-3">
                    <img src="../images/nealogo.png" alt="NEA Logo" class="w-10 h-10">
                    <span class="font-bold text-lg">NEA DTMS</span>
                </div>
            </div>
            <div class="flex items-center space-x-6">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="hover:opacity-75 transition">Logout</a>
            </div>
        </div>
    </nav>

    <div class="flex pt-16">
        <!-- Include Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 w-full lg:w-auto">
            <div class="max-w-7xl mx-auto px-6 py-10">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h1 class="text-4xl font-bold mb-6" style="color: #009246;">Welcome to NEA DTMS</h1>
                    <p class="text-gray-600 text-lg mb-6">
                        This is your Document Tracking & Management System dashboard.
                    </p>
                    
                    <!-- Dashboard Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                        <!-- Card 1 -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6 border-l-4" style="border-color: #009246;">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-600 text-sm">Total Documents</p>
                                    <p class="text-3xl font-bold" style="color: #009246;">0</p>
                                </div>
                                <i class="fas fa-file-alt text-5xl" style="color: #009246; opacity: 0.2;"></i>
                            </div>
                        </div>

                        <!-- Card 2 -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6 border-l-4" style="border-color: #009246;">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-600 text-sm">Pending Approvals</p>
                                    <p class="text-3xl font-bold" style="color: #009246;">0</p>
                                </div>
                                <i class="fas fa-hourglass-half text-5xl" style="color: #009246; opacity: 0.2;"></i>
                            </div>
                        </div>

                        <!-- Card 3 -->
                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-6 border-l-4" style="border-color: #009246;">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-600 text-sm">Completed</p>
                                    <p class="text-3xl font-bold" style="color: #009246;">0</p>
                                </div>
                                <i class="fas fa-check-circle text-5xl" style="color: #009246; opacity: 0.2;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            sidebarOverlay.classList.toggle('hidden');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.add('hidden');
            sidebarOverlay.classList.add('hidden');
        });

        // Close sidebar on link click (mobile)
        document.querySelectorAll('.nav-item').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    sidebar.classList.add('hidden');
                    sidebarOverlay.classList.add('hidden');
                }
            });
        });

        // Set active nav item based on current page
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.getAttribute('href') === window.location.pathname.split('/').pop()) {
                item.classList.add('active');
                item.style.borderLeft = '4px solid #009246';
            } else {
                item.classList.remove('active');
                item.style.borderLeft = 'none';
            }
        });
    </script>

</body>
</html>