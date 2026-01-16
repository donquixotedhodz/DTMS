<?php
require 'session_check.php';
require 'config.php';

$message = '';
$error = '';
$upload_dir = '../uploads/documents/';

// Create upload directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle Archive Action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'archive') {
    $document_id = intval($_POST['document_id']);
    
    $archive_sql = "UPDATE documents SET status = 'archived' WHERE id = $document_id";
    
    if ($conn->query($archive_sql) === TRUE) {
        // Log the action
        $log_sql = "INSERT INTO document_tracking (document_id, action, performed_by, notes) 
                   VALUES ($document_id, 'Document Archived', " . $_SESSION['user_id'] . ", 'Document moved to archive')";
        $conn->query($log_sql);
        
        echo 'success';
    } else {
        echo 'error';
    }
    exit();
}

// Handle Document Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload') {
    $document_date = !empty($_POST['document_date']) ? $_POST['document_date'] : null;
    $document_name = $conn->real_escape_string($_POST['document_name']);
    $document_type = $conn->real_escape_string($_POST['document_type']);
    $department = $conn->real_escape_string($_POST['department']);
    $remarks = $conn->real_escape_string($_POST['remarks']);
    $from_who = $conn->real_escape_string($_POST['from_who']);
    $received_by = $conn->real_escape_string($_POST['received_by']);
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    
    // Validate inputs
    if (empty($document_name) || empty($document_type) || empty($department) || empty($from_who) || empty($received_by)) {
        $error = 'Document name, type, department, from who, and received by are required.';
    } else {
        // Generate unique document number
        $document_number = 'DOC-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Save to database with tracking information
        $insert_sql = "INSERT INTO documents (document_number, title, description, document_type, department, from_who, received_by, deadline, created_by, status, document_date) 
                      VALUES ('$document_number', '$document_name', '$remarks', '$document_type', '$department', '$from_who', '$received_by', " . ($deadline ? "'$deadline'" : 'NULL') . ", " . $_SESSION['user_id'] . ", 'submitted', " . ($document_date ? "'$document_date'" : 'NULL') . ")";
        
        if ($conn->query($insert_sql) === TRUE) {
            $document_id = $conn->insert_id;
            
            // Log the action
            $log_sql = "INSERT INTO document_tracking (document_id, action, performed_by, notes) 
                       VALUES ($document_id, 'Document Registered', " . $_SESSION['user_id'] . ", 'Document registered with tracking details')";
            $conn->query($log_sql);
            
            $message = 'Document registered successfully! Document Number: ' . $document_number;
        } else {
            $error = 'Error saving document to database: ' . $conn->error;
        }
    }
}

// Handle Document Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $document_id = intval($_POST['document_id']);
    $document_date = !empty($_POST['document_date']) ? $_POST['document_date'] : null;
    $document_name = $conn->real_escape_string($_POST['document_name']);
    $document_type = $conn->real_escape_string($_POST['document_type']);
    $department = $conn->real_escape_string($_POST['department']);
    $remarks = $conn->real_escape_string($_POST['remarks']);
    $from_who = $conn->real_escape_string($_POST['from_who']);
    $received_by = $conn->real_escape_string($_POST['received_by']);
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    
    // Validate inputs
    if (empty($document_name) || empty($document_type) || empty($department) || empty($from_who) || empty($received_by)) {
        $error = 'Document name, type, department, from who, and received by are required.';
    } else {
        // Update document in database
        $update_sql = "UPDATE documents SET 
                      title = '$document_name', 
                      description = '$remarks', 
                      document_type = '$document_type', 
                      department = '$department', 
                      from_who = '$from_who', 
                      received_by = '$received_by', 
                      deadline = " . ($deadline ? "'$deadline'" : 'NULL') . ", 
                      document_date = " . ($document_date ? "'$document_date'" : 'NULL') . "
                      WHERE id = $document_id";
        
        if ($conn->query($update_sql) === TRUE) {
            // Log the action
            $log_sql = "INSERT INTO document_tracking (document_id, action, performed_by, notes) 
                       VALUES ($document_id, 'Document Updated', " . $_SESSION['user_id'] . ", 'Document details updated')";
            $conn->query($log_sql);
            
            $message = 'Document updated successfully!';
        } else {
            $error = 'Error updating document: ' . $conn->error;
        }
    }
}

// Fetch all documents with tracking details (excluding archived)
$documents_sql = "SELECT d.id, d.document_number, d.title, d.description, d.document_type, 
                  d.department, d.from_who, d.received_by, d.document_date,
                  d.status, d.priority, d.created_by, d.assigned_to, d.in_charge, 
                  d.current_location, d.handled_by, d.deadline, d.file_path, 
                  d.created_at, d.updated_at,
                  u.first_name, u.last_name, 
                  u1.first_name as in_charge_first, u1.last_name as in_charge_last,
                  u2.first_name as handled_first, u2.last_name as handled_last
                  FROM documents d 
                  LEFT JOIN users u ON d.created_by = u.id
                  LEFT JOIN users u1 ON d.in_charge = u1.id
                  LEFT JOIN users u2 ON d.handled_by = u2.id
                  WHERE d.status != 'archived'
                  ORDER BY d.created_at DESC";
$documents_result = $conn->query($documents_sql);

// Fetch users for assignment dropdown
$users_sql = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE status = 'active' AND id != " . $_SESSION['user_id'] . " ORDER BY first_name";
$users_result = $conn->query($users_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents - NEA DTMS</title>
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
            display: none !important;
        }
        .modal.show {
            display: flex !important;
        }
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            width: 100%;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        .file-label {
            display: block;
            padding: 2rem;
            text-align: center;
            border: 2px dashed #009246;
            border-radius: 0.5rem;
            background-color: #f0f9f7;
            transition: all 0.3s;
        }
        .file-input-wrapper:hover .file-label {
            background-color: #e0f2ef;
            border-color: #007030;
        }
        /* Modal specific styles */
        .modal-content {
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }
        .modal-header {
            flex-shrink: 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-body {
            flex: 1;
            overflow-y: auto;
        }
        .modal-footer {
            flex-shrink: 0;
            border-top: 1px solid #e5e7eb;
            background-color: #f9fafb;
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
                    <img src="../images/nealogo.svg" alt="NEA Logo" class="w-10 h-10">
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
                    <h1 class="text-4xl font-bold" style="color: #009246;">Document Register</h1>
                    <button onclick="openUploadModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Register Document</span>
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

                <!-- Documents List -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <?php if ($documents_result && $documents_result->num_rows > 0): ?>
                        <div>
                            <table class="w-full table-fixed">
                                <thead style="background-color: #009246;" class="text-white">
                                    <tr>
                                        <th class="px-3 py-3 text-left font-semibold text-xs w-24">Document #</th>
                                        <th class="px-3 py-3 text-left font-semibold text-xs w-32">Name</th>
                                        <th class="px-3 py-3 text-left font-semibold text-xs w-20">Type</th>
                                        <th class="px-3 py-3 text-left font-semibold text-xs w-20">Department</th>
                                        <th class="px-3 py-3 text-left font-semibold text-xs w-24">From</th>
                                        <th class="px-3 py-3 text-left font-semibold text-xs w-24">Received By</th>
                                        <th class="px-3 py-3 text-left font-semibold text-xs w-20">Deadline</th>
                                        <th class="px-3 py-3 text-left font-semibold text-xs w-16">Status</th>
                                        <th class="px-3 py-3 text-center font-semibold text-xs w-20">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($doc = $documents_result->fetch_assoc()): ?>
                                        <tr class="border-b hover:bg-gray-50 transition">
                                            <td class="px-3 py-3 text-xs text-gray-800 font-semibold truncate"><?php echo htmlspecialchars($doc['document_number']); ?></td>
                                            <td class="px-3 py-3 text-xs text-gray-800 truncate"><?php echo htmlspecialchars($doc['title']); ?></td>
                                            <td class="px-3 py-3 text-xs text-gray-800 truncate"><?php echo htmlspecialchars($doc['document_type'] ?? ''); ?></td>
                                            <td class="px-3 py-3 text-xs text-gray-800 truncate"><?php echo htmlspecialchars($doc['department'] ?? ''); ?></td>
                                            <td class="px-3 py-3 text-xs text-gray-800 truncate"><?php echo htmlspecialchars($doc['from_who'] ?? ''); ?></td>
                                            <td class="px-3 py-3 text-xs text-gray-800 truncate"><?php echo htmlspecialchars($doc['received_by'] ?? ''); ?></td>
                                            <td class="px-3 py-3 text-xs text-gray-800 truncate">
                                                <?php echo $doc['deadline'] ? date('M d, Y', strtotime($doc['deadline'])) : '-'; ?>
                                            </td>
                                            <td class="px-3 py-3 text-xs">
                                                <span class="px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800 whitespace-nowrap">
                                                    <?php echo ucfirst($doc['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-3 py-3 text-center">
                                                <div class="flex justify-center gap-1">
                                                    <button onclick="openEditModal(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['title']); ?>', '<?php echo htmlspecialchars($doc['document_type']); ?>', '<?php echo htmlspecialchars($doc['department'] ?? ''); ?>', '<?php echo addslashes($doc['description']); ?>', '<?php echo htmlspecialchars($doc['from_who'] ?? ''); ?>', '<?php echo htmlspecialchars($doc['received_by'] ?? ''); ?>', '<?php echo $doc['document_date']; ?>', '<?php echo $doc['deadline']; ?>')" class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1.5 rounded text-xs font-semibold transition" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="openViewDetailsModal(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['title']); ?>', '<?php echo addslashes($doc['description']); ?>', '<?php echo htmlspecialchars($doc['document_type']); ?>', '<?php echo htmlspecialchars($doc['department']); ?>', '<?php echo htmlspecialchars($doc['from_who']); ?>', '<?php echo htmlspecialchars($doc['received_by']); ?>', '<?php echo $doc['document_date'] ? date('M d, Y', strtotime($doc['document_date'])) : date('M d, Y', strtotime($doc['created_at'])); ?>', '<?php echo htmlspecialchars($doc['document_number']); ?>', '<?php echo $doc['status']; ?>', '<?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?>', '<?php echo $doc['deadline'] ? date('M d, Y', strtotime($doc['deadline'])) : ''; ?>')" class="bg-gray-600 hover:bg-gray-700 text-white px-2 py-1.5 rounded text-xs font-semibold transition" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="archiveDocument(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['document_number']); ?>')" class="bg-orange-600 hover:bg-orange-700 text-white px-2 py-1.5 rounded text-xs font-semibold transition" title="Archive">
                                                        <i class="fas fa-archive"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg">No documents registered yet.</p>
                            <button onclick="openUploadModal()" class="mt-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                Register Your First Document
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

   <!-- Upload Document Modal - Updated 2 Column Layout -->
<div id="uploadModal" class="modal fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl">
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold" style="color: #009246;">Register New Document</h2>
            <button onclick="closeUploadModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="uploadForm" method="POST" action="">
            <input type="hidden" name="action" value="upload">
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Document Date <span class="text-red-500">*</span></label>
                        <input type="date" name="document_date" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="border-color: #ddd;">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Document Name <span class="text-red-500">*</span></label>
                        <input type="text" name="document_name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="border-color: #ddd;" placeholder="Enter document name">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Document Type <span class="text-red-500">*</span></label>
                        <select name="document_type" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="border-color: #ddd;">
                            <option value="">Select Document Type</option>
                            <option value="Memo">Memorandum</option>
                            <option value="Report">Report</option>
                            <option value="Proposal">Proposal</option>
                            <option value="Letter">Letter</option>
                            <option value="Contract">Contract</option>
                            <option value="Invoice">Invoice</option>
                            <option value="Receipt">Receipt</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Department <span class="text-red-500">*</span></label>
                        <input type="text" name="department" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="border-color: #ddd;" placeholder="Enter department">
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">From Who <span class="text-red-500">*</span></label>
                        <input type="text" name="from_who" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="border-color: #ddd;" placeholder="Enter sender name">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Received By <span class="text-red-500">*</span></label>
                        <input type="text" name="received_by" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="border-color: #ddd;" placeholder="Enter receiver name">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Deadline</label>
                        <input type="date" name="deadline" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="border-color: #ddd;">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Remarks</label>
                        <textarea name="remarks" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="border-color: #ddd;" rows="4" placeholder="Enter remarks or additional notes"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 p-6 border-t border-gray-200 bg-gray-50">
                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-semibold transition">
                    <i class="fas fa-save mr-2"></i>Register Document
                </button>
                <button type="button" onclick="closeUploadModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-3 rounded-lg font-semibold">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Update modal styles */
.modal {
    display: none !important;
}
.modal.show {
    display: flex !important;
}
</style>

    <!-- Edit Document Modal -->
    <div id="editModal" class="modal fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-start sm:items-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl my-4 sm:my-8 modal-content">
            <div class="modal-header flex justify-between items-center p-6 sm:p-8">
                <h2 class="text-2xl font-bold" style="color: #009246;">Edit Document</h2>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <form id="editForm" method="POST" action="" class="modal-body p-6 sm:p-8 space-y-6">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="document_id" id="editDocumentId">

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Document Date</label>
                    <input type="date" name="document_date" id="editDocumentDate" class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Document Name</label>
                    <input type="text" name="document_name" id="editDocumentName" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;" placeholder="Enter document name">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Document Type</label>
                        <select name="document_type" id="editDocumentType" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                            <option value="">Select Document Type</option>
                            <option value="Memo">Memo</option>
                            <option value="Report">Report</option>
                            <option value="Proposal">Proposal</option>
                            <option value="Letter">Letter</option>
                            <option value="Contract">Contract</option>
                            <option value="Invoice">Invoice</option>
                            <option value="Receipt">Receipt</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Department</label>
                        <input type="text" name="department" id="editDepartment" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;" placeholder="Enter department">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Remarks</label>
                    <textarea name="remarks" id="editRemarks" class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;" rows="3" placeholder="Enter remarks or additional notes"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">From Who</label>
                        <input type="text" name="from_who" id="editFromWho" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;" placeholder="Enter sender name">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Received By</label>
                        <input type="text" name="received_by" id="editReceivedBy" required class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;" placeholder="Enter receiver name">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Deadline (Optional)</label>
                    <input type="date" name="deadline" id="editDeadline" class="w-full px-4 py-2 border rounded-lg focus:outline-none" style="border-color: #ddd;">
                </div>

            </form>

            <div class="modal-footer flex flex-col sm:flex-row gap-3 p-6 sm:p-8">
                <button type="submit" form="editForm" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
                <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg font-semibold">Cancel</button>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="detailsModal" class="modal fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-start sm:items-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl my-4 sm:my-8 modal-content">
            <div class="modal-header flex justify-between items-center p-6 sm:p-8">
                <h2 class="text-2xl font-bold" style="color: #009246;">Document Details</h2>
                <button onclick="closeDetailsModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <div id="detailsContent" class="modal-body p-6 sm:p-8 space-y-4">
                <!-- Details will be populated by JavaScript -->
            </div>

            <div class="modal-footer flex gap-2 p-6 sm:p-8">
                <button type="button" onclick="closeDetailsModal()" class="w-full bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg font-semibold">Close</button>
            </div>
        </div>
    </div>    <script>
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

        // File input handling
        const fileInput = document.getElementById('documentFile');
        const fileLabel = document.querySelector('.file-label');
        const fileInfo = document.getElementById('fileInfo');

        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                    fileInfo.textContent = `Selected: ${file.name} (${sizeMB}MB)`;
                    fileLabel.style.backgroundColor = '#e0f2ef';
                }
            });

            // Drag and drop
            fileLabel.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileLabel.style.backgroundColor = '#d0f0ed';
            });

            fileLabel.addEventListener('dragleave', () => {
                fileLabel.style.backgroundColor = '#f0f9f7';
            });

            fileLabel.addEventListener('drop', (e) => {
                e.preventDefault();
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    const file = files[0];
                    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                    fileInfo.textContent = `Selected: ${file.name} (${sizeMB}MB)`;
                    fileLabel.style.backgroundColor = '#f0f9f7';
                }
            });
        }

        // Modal Functions
        function openUploadModal() {
            const modal = document.getElementById('uploadModal');
            modal.classList.add('show');
            modal.style.display = 'flex';
        }

        function closeUploadModal() {
            const modal = document.getElementById('uploadModal');
            modal.classList.remove('show');
            modal.style.display = 'none';
        }

        function openViewDetailsModal(id, title, description, type, department, fromWho, receivedBy, docDate, docNum, status, author, deadline) {
            
            const html = `
                <div class="space-y-4">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                        <p class="text-gray-600 text-sm"><strong>Document Number:</strong></p>
                        <p class="text-gray-800 font-semibold">${docNum}</p>
                    </div>
                    
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Document Name:</strong></p>
                        <p class="text-gray-800">${title}</p>
                    </div>
                    
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Remarks:</strong></p>
                        <p class="text-gray-800">${description || 'No remarks provided'}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600 text-sm"><strong>Document Type:</strong></p>
                            <p class="text-gray-800">${type}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm"><strong>Department:</strong></p>
                            <p class="text-gray-800">${department}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600 text-sm"><strong>From Who:</strong></p>
                            <p class="text-gray-800">${fromWho}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm"><strong>Received By:</strong></p>
                            <p class="text-gray-800">${receivedBy}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600 text-sm"><strong>Status:</strong></p>
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm"><strong>Registered By:</strong></p>
                            <p class="text-gray-800">${author}</p>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Document Date:</strong></p>
                        <p class="text-gray-800">${docDate}</p>
                    </div>
                    
                    ${deadline ? `
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Deadline:</strong></p>
                        <p class="text-gray-800">${deadline}</p>
                    </div>
                    ` : ''}
                </div>
            `;
            document.getElementById('detailsContent').innerHTML = html;
            const modal = document.getElementById('detailsModal');
            modal.classList.add('show');
            modal.style.display = 'flex';
        }

        function closeDetailsModal() {
            const modal = document.getElementById('detailsModal');
            modal.classList.remove('show');
            modal.style.display = 'none';
        }

        function openEditModal(id, name, type, dept, remarks, fromWho, receivedBy, docDate, deadline) {
            document.getElementById('editDocumentId').value = id;
            document.getElementById('editDocumentName').value = name;
            document.getElementById('editDocumentType').value = type;
            document.getElementById('editDepartment').value = dept;
            document.getElementById('editRemarks').value = remarks;
            document.getElementById('editFromWho').value = fromWho;
            document.getElementById('editReceivedBy').value = receivedBy;
            document.getElementById('editDocumentDate').value = docDate;
            document.getElementById('editDeadline').value = deadline;

            const modal = document.getElementById('editModal');
            modal.classList.add('show');
            modal.style.display = 'flex';
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('show');
            modal.style.display = 'none';
        }

        // Archive Document Function
        function archiveDocument(id, docNum) {
            if (confirm(`Are you sure you want to archive document ${docNum}?`)) {
                const formData = new FormData();
                formData.append('action', 'archive');
                formData.append('document_id', id);

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.includes('success')) {
                        alert('Document archived successfully!');
                        location.reload();
                    } else {
                        alert('Error archiving document');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error archiving document');
                });
            }
        }

        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            const uploadModal = document.getElementById('uploadModal');
            const editModal = document.getElementById('editModal');
            const detailsModal = document.getElementById('detailsModal');

            if (e.target === uploadModal) {
                closeUploadModal();
            }
            if (e.target === editModal) {
                closeEditModal();
            }
            if (e.target === detailsModal) {
                closeDetailsModal();
            }
        });
    </script>

</body>
</html>
