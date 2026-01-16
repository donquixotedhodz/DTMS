<?php
require 'session_check.php';
require 'config.php';

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$error = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $password = $_POST['password'];
    $role = $conn->real_escape_string($_POST['role']);
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'All fields are required.';
    } else if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if username already exists
        $check_sql = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (username, email, first_name, last_name, password, role, status) 
                          VALUES ('$username', '$email', '$first_name', '$last_name', '$hashed_password', '$role', 'active')";
            
            if ($conn->query($insert_sql) === TRUE) {
                $message = 'User added successfully!';
            } else {
                $error = 'Error adding user: ' . $conn->error;
            }
        }
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $user_id = intval($_POST['user_id']);
    $email = $conn->real_escape_string($_POST['email']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $role = $conn->real_escape_string($_POST['role']);
    $status = $conn->real_escape_string($_POST['status']);
    
    if (empty($email) || empty($first_name) || empty($last_name)) {
        $error = 'All fields are required.';
    } else {
        $update_sql = "UPDATE users SET email='$email', first_name='$first_name', last_name='$last_name', role='$role', status='$status' WHERE id=$user_id";
        
        if ($conn->query($update_sql) === TRUE) {
            $message = 'User updated successfully!';
        } else {
            $error = 'Error updating user: ' . $conn->error;
        }
    }
}

// Handle Reset Password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reset_password') {
    $user_id = intval($_POST['user_id']);
    $new_password = $_POST['new_password'];
    
    if (empty($new_password) || strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password='$hashed_password' WHERE id=$user_id";
        
        if ($conn->query($update_sql) === TRUE) {
            $message = 'Password reset successfully!';
        } else {
            $error = 'Error resetting password: ' . $conn->error;
        }
    }
}

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $user_id = intval($_POST['user_id']);
    
    // Prevent deleting the current admin user
    if ($user_id == $_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $delete_sql = "DELETE FROM users WHERE id=$user_id";
        
        if ($conn->query($delete_sql) === TRUE) {
            $message = 'User deleted successfully!';
        } else {
            $error = 'Error deleting user: ' . $conn->error;
        }
    }
}

// Fetch all users
$users_sql = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = $conn->query($users_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - NEA DTMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            transition: transform 0.3s ease;
        }
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        .modal {
            display: none;
        }
        .modal.show {
            display: flex;
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
                <!-- Page Header -->
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-4xl font-bold" style="color: #009246;">User Management</h1>
                    <button onclick="openAddUserModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Add User</span>
                    </button>
                </div>

                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                        <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Users Table -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead style="background-color: #009246;">
                                <tr class="text-white">
                                    <th class="px-6 py-4 text-left text-sm font-semibold">ID</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Username</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Email</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Name</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Role</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Created</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($users_result && $users_result->num_rows > 0): ?>
                                    <?php while ($user = $users_result->fetch_assoc()): ?>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm"><?php echo $user['id']; ?></td>
                                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td class="px-6 py-4 text-sm">
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: #e8f5e9; color: #009246;">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $user['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?php echo ucfirst($user['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td class="px-6 py-4 text-sm">
                                                <div class="flex space-x-2">
                                                    <button onclick="openEditUserModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['first_name']); ?>', '<?php echo htmlspecialchars($user['last_name']); ?>', '<?php echo $user['role']; ?>', '<?php echo $user['status']; ?>')" class="text-blue-600 hover:text-blue-800">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="openResetPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="text-yellow-600 hover:text-yellow-800">
                                                        <i class="fas fa-key"></i>
                                                    </button>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="text-red-600 hover:text-red-800">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">No users found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal fixed inset-0 bg-black bg-opacity-50 z-50 justify-center items-center">
        <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold" style="color: #009246;">Add New User</h2>
                <button onclick="closeAddUserModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="add">

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Username</label>
                    <input type="text" name="username" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">First Name</label>
                        <input type="text" name="first_name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Last Name</label>
                        <input type="text" name="last_name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;" placeholder="Min 6 characters">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Role</label>
                    <select name="role" class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                        <option value="staff">Staff</option>
                        <option value="manager">Manager</option>
                        <option value="viewer">Viewer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold">Add User</button>
                    <button type="button" onclick="closeAddUserModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg font-semibold">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal fixed inset-0 bg-black bg-opacity-50 z-50 justify-center items-center">
        <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold" style="color: #009246;">Edit User</h2>
                <button onclick="closeEditUserModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id" value="">

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Username (Read-only)</label>
                    <input type="text" id="edit_username" disabled class="w-full px-4 py-2 border rounded-lg bg-gray-100" style="border-color: #ddd;">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Email</label>
                    <input type="email" name="email" id="edit_email" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">First Name</label>
                        <input type="text" name="first_name" id="edit_first_name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Last Name</label>
                        <input type="text" name="last_name" id="edit_last_name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Role</label>
                    <select name="role" id="edit_role" class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                        <option value="staff">Staff</option>
                        <option value="manager">Manager</option>
                        <option value="viewer">Viewer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Status</label>
                    <select name="status" id="edit_status" class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold">Update User</button>
                    <button type="button" onclick="closeEditUserModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg font-semibold">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal fixed inset-0 bg-black bg-opacity-50 z-50 justify-center items-center">
        <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold" style="color: #009246;">Reset Password</h2>
                <button onclick="closeResetPasswordModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id" value="">

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Username</label>
                    <input type="text" id="reset_username" disabled class="w-full px-4 py-2 border rounded-lg bg-gray-100" style="border-color: #ddd;">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">New Password</label>
                    <input type="password" name="new_password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;" placeholder="Min 6 characters">
                </div>

                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-semibold">Reset Password</button>
                    <button type="button" onclick="closeResetPasswordModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg font-semibold">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal fixed inset-0 bg-black bg-opacity-50 z-50 justify-center items-center">
        <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-red-600 mb-2">Confirm Delete</h2>
                <p class="text-gray-700">Are you sure you want to delete user <strong id="delete_username"></strong>? This action cannot be undone.</p>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="delete_user_id" value="">

                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold">Delete User</button>
                    <button type="button" onclick="closeDeleteConfirmModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg font-semibold">Cancel</button>
                </div>
            </form>
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

        // Modal Functions
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.add('show');
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.remove('show');
        }

        function openEditUserModal(id, username, email, first_name, last_name, role, status) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_first_name').value = first_name;
            document.getElementById('edit_last_name').value = last_name;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_status').value = status;
            document.getElementById('editUserModal').classList.add('show');
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').classList.remove('show');
        }

        function openResetPasswordModal(id, username) {
            document.getElementById('reset_user_id').value = id;
            document.getElementById('reset_username').value = username;
            document.getElementById('resetPasswordModal').classList.add('show');
        }

        function closeResetPasswordModal() {
            document.getElementById('resetPasswordModal').classList.remove('show');
        }

        function confirmDelete(id, username) {
            document.getElementById('delete_user_id').value = id;
            document.getElementById('delete_username').textContent = username;
            document.getElementById('deleteConfirmModal').classList.add('show');
        }

        function closeDeleteConfirmModal() {
            document.getElementById('deleteConfirmModal').classList.remove('show');
        }

        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            const addUserModal = document.getElementById('addUserModal');
            const editUserModal = document.getElementById('editUserModal');
            const resetPasswordModal = document.getElementById('resetPasswordModal');
            const deleteConfirmModal = document.getElementById('deleteConfirmModal');

            if (e.target === addUserModal) {
                closeAddUserModal();
            }
            if (e.target === editUserModal) {
                closeEditUserModal();
            }
            if (e.target === resetPasswordModal) {
                closeResetPasswordModal();
            }
            if (e.target === deleteConfirmModal) {
                closeDeleteConfirmModal();
            }
        });
    </script>

</body>
</html>
