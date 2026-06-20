-- ============================================
-- SCHOOL PAYMENT SYSTEM - DATABASE SCHEMA
-- ============================================

CREATE DATABASE IF NOT EXISTS school_payment_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_payment_db;

-- USERS TABLE (Admin & Cashier)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'cashier') NOT NULL DEFAULT 'cashier',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- STUDENTS TABLE
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    grade_level VARCHAR(20),
    section VARCHAR(50),
    school_year VARCHAR(20),
    major VARCHAR(100) DEFAULT NULL,
    total_fee DECIMAL(10,2) DEFAULT 0.00,
    total_paid DECIMAL(10,2) DEFAULT 0.00,
    balance DECIMAL(10,2) GENERATED ALWAYS AS (total_fee - total_paid) STORED,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- PAYMENT TYPES TABLE
CREATE TABLE IF NOT EXISTS payment_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- TRANSACTIONS TABLE
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_no VARCHAR(20) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    cashier_id INT NOT NULL,
    payment_type_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_time TIME NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (cashier_id) REFERENCES users(id),
    FOREIGN KEY (payment_type_id) REFERENCES payment_types(id)
);

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Default Admin Account (password: admin123)
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', 'admin123', 'System Administrator', 'admin@school.edu', 'admin');

-- Default Cashier (password: cashier123)  
INSERT INTO users (username, password, full_name, email, role) VALUES
('cashier1', 'cashier123', 'Maria Santos', 'cashier@school.edu', 'cashier');
-- Payment Types
INSERT INTO payment_types (type_name, description) VALUES
('Tuition Fee', 'Monthly or semestral tuition payment'),
('Miscellaneous Fee', 'Laboratory, library, and other miscellaneous fees'),
('Enrollment Fee', 'One-time enrollment processing fee'),
('Books & Materials', 'Textbooks and school materials'),
('Sports Fee', 'Athletic and sports program fee'),
('Technology Fee', 'Computer lab and technology usage fee'),
('Graduation Fee', 'Graduation ceremony and diploma fee');

-- Sample Students
INSERT INTO students (student_id, full_name, grade_level, section, school_year, total_fee, total_paid) VALUES
('2024-001', 'Juan Dela Cruz', 'Grade 10', 'Rizal', '2024-2025', 25000.00, 10000.00),
('2024-002', 'Maria Garcia', 'Grade 11', 'STEM-A', '2024-2025', 30000.00, 15000.00),
('2024-003', 'Pedro Reyes', 'Grade 12', 'ABM-B', '2024-2025', 32000.00, 32000.00),
('2024-004', 'Ana Lim', 'Grade 9', 'Bonifacio', '2024-2025', 22000.00, 5000.00),
('2024-005', 'Carlo Mendoza', 'Grade 10', 'Mabini', '2024-2025', 25000.00, 12500.00);