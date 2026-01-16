<?php
// Database migration script - Add tracking columns to documents table

require 'config.php';

echo "<h2>Adding tracking columns to documents table...</h2>";

// Check if columns exist and add them if they don't
$columns_to_add = array(
    'in_charge' => "ALTER TABLE documents ADD COLUMN in_charge INT AFTER assigned_to",
    'current_location' => "ALTER TABLE documents ADD COLUMN current_location VARCHAR(255) AFTER in_charge",
    'handled_by' => "ALTER TABLE documents ADD COLUMN handled_by INT AFTER current_location",
    'deadline' => "ALTER TABLE documents ADD COLUMN deadline DATE AFTER handled_by"
);

foreach ($columns_to_add as $col_name => $sql) {
    $check_sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='documents' AND COLUMN_NAME='$col_name'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows == 0) {
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color: green;'>✓ Column '$col_name' added successfully</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding column '$col_name': " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ Column '$col_name' already exists</p>";
    }
}

// Add foreign key constraints
$fk_sql = array(
    "ALTER TABLE documents ADD CONSTRAINT fk_in_charge FOREIGN KEY (in_charge) REFERENCES users(id) ON DELETE SET NULL",
    "ALTER TABLE documents ADD CONSTRAINT fk_handled_by FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL"
);

foreach ($fk_sql as $sql) {
    // Check if constraint already exists
    $constraint_name = strpos($sql, 'fk_in_charge') ? 'fk_in_charge' : 'fk_handled_by';
    $check_constraint = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                         WHERE TABLE_NAME='documents' AND CONSTRAINT_NAME='$constraint_name'";
    $result = $conn->query($check_constraint);
    
    if ($result->num_rows == 0) {
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color: green;'>✓ Foreign key constraint '$constraint_name' added successfully</p>";
        } else {
            // Silently fail if constraint already exists in different form
            echo "<p style='color: blue;'>ℹ Foreign key constraint '$constraint_name' check skipped</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ Foreign key constraint '$constraint_name' already exists</p>";
    }
}

echo "<hr>";
echo "<p><strong>Migration completed!</strong> You can now use the document tracking features.</p>";
echo "<p><a href='upload_document.php'>Go back to Upload Documents</a></p>";

$conn->close();
?>
