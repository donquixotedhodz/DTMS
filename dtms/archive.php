<?php
require 'session_check.php';
require 'config.php';

$upload_dir = '../uploads/documents/';

// Handle Restore Action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'restore') {
    $document_id = intval($_POST['document_id']);
    
    $restore_sql = "UPDATE documents SET status = 'submitted' WHERE id = $document_id";
    
    if ($conn->query($restore_sql) === TRUE) {
        // Log the action
        $log_sql = "INSERT INTO document_tracking (document_id, action, performed_by, notes) 
                   VALUES ($document_id, 'Document Restored', " . $_SESSION['user_id'] . ", 'Document restored from archive')";
        $conn->query($log_sql);
        
        echo 'success';
    } else {
        echo 'error';
    }
    exit();
}

// Fetch all archived documents with tracking details
$documents_sql = "SELECT d.*, u.first_name, u.last_name, 
                  u1.first_name as in_charge_first, u1.last_name as in_charge_last,
                  u2.first_name as handled_first, u2.last_name as handled_last
                  FROM documents d 
                  LEFT JOIN users u ON d.created_by = u.id
                  LEFT JOIN users u1 ON d.in_charge = u1.id
                  LEFT JOIN users u2 ON d.handled_by = u2.id
                  WHERE d.status = 'archived'
                  ORDER BY d.created_at DESC";
$documents_result = $conn->query($documents_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - NEA DTMS</title>
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
                    <div>
                        <h1 class="text-xl font-bold">NEA DTMS</h1>
                        <p class="text-xs">Document Archive</p>
                    </div>
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
                    <h1 class="text-4xl font-bold" style="color: #009246;">Archived Documents</h1>
                </div>

                <!-- Documents Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if ($documents_result && $documents_result->num_rows > 0): ?>
                        <?php while ($doc = $documents_result->fetch_assoc()): ?>
                            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                                <!-- Card Header -->
                                <div style="background-color: #009246;" class="px-6 py-4 text-white">
                                    <h3 class="text-lg font-bold truncate"><?php echo htmlspecialchars($doc['title']); ?></h3>
                                    <p class="text-sm text-gray-200"><?php echo htmlspecialchars($doc['document_number']); ?></p>
                                </div>

                                <!-- Card Body -->
                                <div class="px-6 py-4">
                                    <p class="text-gray-600 text-sm mb-2">
                                        <strong>Type:</strong> <?php echo htmlspecialchars($doc['document_type']); ?>
                                    </p>
                                    <p class="text-gray-600 text-sm mb-2">
                                        <strong>Priority:</strong>
                                        <span class="px-2 py-1 rounded text-xs font-semibold <?php 
                                            if ($doc['priority'] == 'high') echo 'bg-red-100 text-red-800';
                                            else if ($doc['priority'] == 'medium') echo 'bg-yellow-100 text-yellow-800';
                                            else echo 'bg-green-100 text-green-800';
                                        ?>">
                                            <?php echo ucfirst($doc['priority']); ?>
                                        </span>
                                    </p>
                                    <p class="text-gray-600 text-sm mb-2">
                                        <strong>Uploaded by:</strong> <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?>
                                    </p>
                                    <p class="text-gray-500 text-xs">
                                        <strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($doc['created_at'])); ?>
                                    </p>
                                </div>

                                <!-- Card Footer -->
                                <div class="px-6 py-4 bg-gray-50 border-t flex space-x-3">
                                    <a href="<?php echo $upload_dir . htmlspecialchars($doc['file_path']); ?>" download class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center px-3 py-2 rounded text-sm font-semibold transition">
                                        <i class="fas fa-download mr-1"></i>Download
                                    </a>
                                    <button onclick="openViewDetailsModal(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['title']); ?>', '<?php echo addslashes($doc['description']); ?>', '<?php echo htmlspecialchars($doc['document_type']); ?>', '<?php echo $doc['priority']; ?>', '<?php echo $doc['status']; ?>', '<?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?>', '<?php echo date('M d, Y H:i', strtotime($doc['created_at'])); ?>', '<?php echo htmlspecialchars($doc['document_number']); ?>', '<?php echo htmlspecialchars($doc['file_path']); ?>', '<?php echo $doc['in_charge'] ? htmlspecialchars($doc['in_charge_first'] . ' ' . $doc['in_charge_last']) : ''; ?>', '<?php echo $doc['handled_by'] ? htmlspecialchars($doc['handled_first'] . ' ' . $doc['handled_last']) : ''; ?>', '<?php echo htmlspecialchars($doc['current_location'] ?? ''); ?>', '<?php echo $doc['deadline'] ? date('M d, Y', strtotime($doc['deadline'])) : ''; ?>')" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white text-center px-3 py-2 rounded text-sm font-semibold transition">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </button>
                                    <button onclick="restoreDocument(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['document_number']); ?>')" class="flex-1 bg-green-600 hover:bg-green-700 text-white text-center px-3 py-2 rounded text-sm font-semibold transition">
                                        <i class="fas fa-undo mr-1"></i>Restore
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-3 bg-white rounded-lg shadow-lg p-12 text-center">
                            <i class="fas fa-archive text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg">No archived documents.</p>
                        </div>
                    <?php endif; ?>
                </div>
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
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            sidebarOverlay.classList.toggle('hidden');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.add('hidden');
            sidebarOverlay.classList.add('hidden');
        });

        function openViewDetailsModal(id, title, description, type, priority, status, author, date, docNum, filePath, inCharge, handledBy, location, deadline) {
            // Get file extension for preview
            const fileExt = filePath.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExt);
            const isPDF = fileExt === 'pdf';
            
            // Build preview HTML
            let previewHtml = '';
            if (isImage) {
                previewHtml = `
                    <div>
                        <p class="text-gray-600 text-sm font-semibold mb-2"><strong>File Preview:</strong></p>
                        <div class="border border-gray-300 rounded-lg overflow-hidden bg-gray-100">
                            <img src="../uploads/documents/${filePath}" alt="Document Preview" class="w-full h-auto max-h-80 object-contain">
                        </div>
                    </div>
                `;
            } else if (isPDF) {
                previewHtml = `
                    <div>
                        <p class="text-gray-600 text-sm font-semibold mb-2"><strong>File Preview:</strong></p>
                        <div class="border border-gray-300 rounded-lg overflow-hidden bg-gray-100 h-80">
                            <iframe src="../uploads/documents/${filePath}#toolbar=0" class="w-full h-full" frameborder="0"></iframe>
                        </div>
                    </div>
                `;
            } else {
                previewHtml = `
                    <div>
                        <p class="text-gray-600 text-sm font-semibold mb-2"><strong>File Type:</strong></p>
                        <p class="text-gray-800 text-sm"><i class="fas fa-file mr-2"></i>${fileExt.toUpperCase()} File</p>
                    </div>
                `;
            }
            
            const html = `
                <div class="space-y-4">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                        <p class="text-gray-600 text-sm"><strong>Document Number:</strong></p>
                        <p class="text-gray-800 font-semibold">${docNum}</p>
                    </div>
                    
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Title:</strong></p>
                        <p class="text-gray-800">${title}</p>
                    </div>
                    
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Description:</strong></p>
                        <p class="text-gray-800">${description || 'No description provided'}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600 text-sm"><strong>Document Type:</strong></p>
                            <p class="text-gray-800">${type}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm"><strong>Priority:</strong></p>
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold ${
                                priority === 'high' ? 'bg-red-100 text-red-800' :
                                priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-green-100 text-green-800'
                            }">${priority.charAt(0).toUpperCase() + priority.slice(1)}</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600 text-sm"><strong>Status:</strong></p>
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm"><strong>Uploaded By:</strong></p>
                            <p class="text-gray-800">${author}</p>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Upload Date:</strong></p>
                        <p class="text-gray-800">${date}</p>
                    </div>
                    
                    ${inCharge ? `
                    <div>
                        <p class="text-gray-600 text-sm"><strong>In Charge:</strong></p>
                        <p class="text-gray-800">${inCharge}</p>
                    </div>
                    ` : ''}
                    
                    ${handledBy ? `
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Handled By:</strong></p>
                        <p class="text-gray-800">${handledBy}</p>
                    </div>
                    ` : ''}
                    
                    ${location ? `
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Current Location:</strong></p>
                        <p class="text-gray-800">${location}</p>
                    </div>
                    ` : ''}
                    
                    ${deadline ? `
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Deadline:</strong></p>
                        <p class="text-gray-800">${deadline}</p>
                    </div>
                    ` : ''}
                    
                    ${previewHtml}
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

        // Restore Document Function
        function restoreDocument(id, docNum) {
            if (confirm(`Are you sure you want to restore document ${docNum}?`)) {
                const formData = new FormData();
                formData.append('action', 'restore');
                formData.append('document_id', id);

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.includes('success')) {
                        alert('Document restored successfully!');
                        location.reload();
                    } else {
                        alert('Error restoring document');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error restoring document');
                });
            }
        }

        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            const detailsModal = document.getElementById('detailsModal');

            if (e.target === detailsModal) {
                closeDetailsModal();
            }
        });
    </script>

</body>
</html>
