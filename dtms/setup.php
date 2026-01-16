<?php
// Database setup script - Run this once to create the database and tables

$servername = "localhost";
$username = "root";
$password = "";

// Create connection (without selecting database)
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS nea_dtms";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

$conn->select_db("nea_dtms");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('admin', 'manager', 'staff', 'viewer') DEFAULT 'staff',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create documents table
$sql = "CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_number VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    document_type VARCHAR(50) NOT NULL,
    department VARCHAR(100),
    from_who VARCHAR(255),
    received_by VARCHAR(255),
    document_date DATE,
    status ENUM('draft', 'submitted', 'approved', 'archived') DEFAULT 'draft',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_by INT NOT NULL,
    assigned_to INT,
    in_charge INT,
    current_location VARCHAR(255),
    handled_by INT,
    deadline DATE,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (in_charge) REFERENCES users(id),
    FOREIGN KEY (handled_by) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Documents table created successfully<br>";
} else {
    echo "Error creating documents table: " . $conn->error . "<br>";
}

// Create document_tracking table
$sql = "CREATE TABLE IF NOT EXISTS document_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    performed_by INT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (document_id) REFERENCES documents(id),
    FOREIGN KEY (performed_by) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Document tracking table created successfully<br>";
} else {
    echo "Error creating document tracking table: " . $conn->error . "<br>";
}

// Create audit_log table
$sql = "CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Audit log table created successfully<br>";
} else {
    echo "Error creating audit log table: " . $conn->error . "<br>";
}

// Add new columns to documents table if they don't exist (for existing installations)
$alter_sqls = array(
    "ALTER TABLE documents ADD COLUMN department VARCHAR(100)",
    "ALTER TABLE documents ADD COLUMN from_who VARCHAR(255)",
    "ALTER TABLE documents ADD COLUMN received_by VARCHAR(255)",
    "ALTER TABLE documents ADD COLUMN document_date DATE"
);

foreach ($alter_sqls as $alter_sql) {
    $result = @$conn->query($alter_sql);
    if ($result === TRUE) {
        echo "Database column added<br>";
    } else {
        // Column already exists or other error - this is okay
        if (strpos($conn->error, "Duplicate column") !== false || strpos($conn->error, "already exists") !== false) {
            // Column already exists, which is fine
        } else {
            echo "Database update note: " . $conn->error . "<br>";
        }
    }
}

// Insert default admin user (password: admin123)
$default_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, password, email, first_name, last_name, role) 
        VALUES ('admin', '$default_password', 'admin@nea.gov', 'System', 'Administrator', 'admin')
        ON DUPLICATE KEY UPDATE username=username";

if ($conn->query($sql) === TRUE) {
    echo "Default admin user created/verified<br>";
} else {
    echo "Error creating admin user: " . $conn->error . "<br>";
}

$conn->close();
echo "<br><strong>Database setup completed!</strong><br>";
echo "Default login credentials:<br>";
echo "Username: admin<br>";
echo "Password: admin123<br>";
?>