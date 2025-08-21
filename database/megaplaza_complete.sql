-- Mega School Plaza - Complete Database Setup for XAMPP
-- Run this file in phpMyAdmin or MySQL command line

-- Create database
CREATE DATABASE IF NOT EXISTS megaplaza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE megaplaza;

-- Drop existing tables if they exist (for clean installation)
DROP TABLE IF EXISTS payment_receipts;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS maintenance_requests;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS leases;
DROP TABLE IF EXISTS shops;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS audit_logs;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'manager', 'tenant') NOT NULL DEFAULT 'tenant',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    reset_token VARCHAR(100),
    reset_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role)
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Shops table
CREATE TABLE shops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shop_number VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    size_sqft DECIMAL(8,2),
    monthly_rent DECIMAL(10,2) NOT NULL,
    security_deposit DECIMAL(10,2),
    floor_level INT DEFAULT 1,
    status ENUM('available', 'occupied', 'maintenance', 'reserved') NOT NULL DEFAULT 'available',
    amenities JSON,
    images JSON,
    location_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_category (category_id),
    INDEX idx_rent (monthly_rent)
);

-- Applications table
CREATE TABLE applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    shop_id INT NOT NULL,
    business_name VARCHAR(200) NOT NULL,
    business_type VARCHAR(100),
    business_description TEXT,
    experience_years INT,
    monthly_revenue DECIMAL(12,2),
    employees_count INT,
    lease_duration INT DEFAULT 12,
    preferred_start_date DATE,
    references TEXT,
    documents JSON,
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'withdrawn') NOT NULL DEFAULT 'pending',
    admin_notes TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_shop (shop_id),
    INDEX idx_status (status)
);

-- Leases table
CREATE TABLE leases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    shop_id INT NOT NULL,
    lease_start DATE NOT NULL,
    lease_end DATE NOT NULL,
    monthly_rent DECIMAL(10,2) NOT NULL,
    security_deposit DECIMAL(10,2),
    status ENUM('active', 'expired', 'terminated', 'pending') NOT NULL DEFAULT 'pending',
    terms TEXT,
    signed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_shop (shop_id),
    INDEX idx_status (status),
    INDEX idx_dates (lease_start, lease_end)
);

-- Payments table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lease_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_type ENUM('rent', 'deposit', 'maintenance', 'penalty', 'other') NOT NULL DEFAULT 'rent',
    payment_method ENUM('cash', 'bank_transfer', 'check', 'online', 'manual_upload') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    due_date DATE,
    paid_date DATE,
    late_fee DECIMAL(8,2) DEFAULT 0,
    description TEXT,
    attachment VARCHAR(255),
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by INT,
    verified_at TIMESTAMP NULL,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lease_id) REFERENCES leases(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lease (lease_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
);

-- Payment receipts table (for manual uploads)
CREATE TABLE payment_receipts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    INDEX idx_payment (payment_id)
);

-- Maintenance requests table
CREATE TABLE maintenance_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    shop_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
    assigned_to INT,
    estimated_cost DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    scheduled_date DATE,
    completed_date DATE,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_shop (shop_id),
    INDEX idx_status (status)
);

-- Documents table
CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    application_id INT,
    lease_id INT,
    document_type VARCHAR(100) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (lease_id) REFERENCES leases(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_application (application_id),
    INDEX idx_lease (lease_id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') NOT NULL DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
);

-- System settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Audit logs table
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('Retail', 'General retail and merchandise stores'),
('Food & Beverage', 'Restaurants, cafes, and food outlets'),
('Services', 'Professional and personal services'),
('Electronics', 'Electronics and technology stores'),
('Fashion', 'Clothing and fashion accessories'),
('Health & Beauty', 'Health, wellness, and beauty services'),
('Education', 'Educational and training services'),
('Entertainment', 'Entertainment and recreational services');

-- Insert demo users (passwords are hashed for 'password123')
INSERT INTO users (username, email, password_hash, first_name, last_name, phone, role, status, email_verified) VALUES
('admin', 'admin@megaplaza.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', '+1234567890', 'admin', 'active', TRUE),
('manager1', 'manager@megaplaza.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Manager', '+1234567891', 'manager', 'active', TRUE),
('tenant1', 'tenant1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', '+1234567892', 'tenant', 'active', TRUE),
('tenant2', 'tenant2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike', 'Chen', '+1234567893', 'tenant', 'active', TRUE),
('tenant3', 'tenant3@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa', 'Rodriguez', '+1234567894', 'tenant', 'active', TRUE);

-- Insert demo shops
INSERT INTO shops (shop_number, title, description, category_id, size_sqft, monthly_rent, security_deposit, floor_level, status, amenities, location_details) VALUES
('A101', 'Prime Corner Shop', 'Excellent corner location with high foot traffic, perfect for retail business', 1, 450.00, 2500.00, 5000.00, 1, 'available', '["Air Conditioning", "Security System", "WiFi Ready", "Parking Space"]', 'Ground floor corner unit with street-facing windows'),
('A102', 'Cozy Cafe Space', 'Perfect for small restaurant or cafe with kitchen facilities', 2, 380.00, 2200.00, 4400.00, 1, 'available', '["Kitchen Equipment", "Ventilation System", "Water Connection", "Waste Disposal"]', 'Ground floor with outdoor seating potential'),
('A103', 'Professional Office', 'Modern office space suitable for professional services', 3, 320.00, 1800.00, 3600.00, 1, 'occupied', '["Air Conditioning", "High-Speed Internet", "Security System", "Reception Area"]', 'Ground floor professional suite'),
('B201', 'Electronics Showroom', 'Spacious showroom perfect for electronics and gadgets', 4, 520.00, 2800.00, 5600.00, 2, 'available', '["Display Lighting", "Security System", "Climate Control", "Storage Area"]', 'Second floor with elevator access'),
('B202', 'Fashion Boutique', 'Stylish space ideal for clothing and fashion accessories', 5, 400.00, 2300.00, 4600.00, 2, 'available', '["Fitting Rooms", "Display Windows", "Security System", "Storage Space"]', 'Second floor with natural lighting'),
('B203', 'Beauty Salon', 'Well-equipped space for beauty and wellness services', 6, 350.00, 2000.00, 4000.00, 2, 'maintenance', '["Plumbing", "Electrical Setup", "Ventilation", "Waiting Area"]', 'Second floor corner unit'),
('C301', 'Training Center', 'Large space suitable for educational and training purposes', 7, 600.00, 3200.00, 6400.00, 3, 'available', '["Projector Setup", "Whiteboard", "Air Conditioning", "Separate Entrance"]', 'Third floor with conference facilities'),
('C302', 'Entertainment Zone', 'Fun space perfect for gaming or entertainment business', 8, 480.00, 2600.00, 5200.00, 3, 'reserved', '["Sound System", "Gaming Setup", "Lounge Area", "Snack Bar"]', 'Third floor entertainment complex');

-- Insert demo applications
INSERT INTO applications (user_id, shop_id, business_name, business_type, business_description, experience_years, monthly_revenue, employees_count, lease_duration, preferred_start_date, status, admin_notes) VALUES
(3, 1, 'Sarah\'s Boutique', 'Fashion Retail', 'High-end fashion boutique specializing in women\'s clothing and accessories', 5, 15000.00, 3, 24, '2024-09-01', 'approved', 'Excellent business plan and references'),
(4, 2, 'Mike\'s Tech Hub', 'Electronics', 'Computer repair and electronics retail store', 8, 12000.00, 2, 12, '2024-08-15', 'under_review', 'Good technical background, reviewing financial documents'),
(5, 4, 'Lisa\'s Learning Center', 'Education', 'Private tutoring and educational services', 3, 8000.00, 4, 18, '2024-10-01', 'pending', 'Application recently submitted');

-- Insert demo leases
INSERT INTO leases (application_id, user_id, shop_id, lease_start, lease_end, monthly_rent, security_deposit, status, signed_at) VALUES
(1, 3, 1, '2024-09-01', '2026-08-31', 2500.00, 5000.00, 'active', '2024-08-15 10:30:00');

-- Insert demo payments
INSERT INTO payments (lease_id, user_id, amount, payment_type, payment_method, status, due_date, paid_date, description) VALUES
(1, 3, 5000.00, 'deposit', 'bank_transfer', 'completed', '2024-08-15', '2024-08-15', 'Security deposit payment'),
(1, 3, 2500.00, 'rent', 'bank_transfer', 'completed', '2024-09-01', '2024-09-01', 'September 2024 rent'),
(1, 3, 2500.00, 'rent', 'manual_upload', 'pending', '2024-10-01', NULL, 'October 2024 rent - receipt uploaded');

-- Insert demo maintenance requests
INSERT INTO maintenance_requests (user_id, shop_id, title, description, priority, status) VALUES
(3, 1, 'Air Conditioning Issue', 'AC unit not cooling properly, needs inspection', 'high', 'open'),
(4, 2, 'Plumbing Leak', 'Small leak under the sink in the kitchen area', 'medium', 'in_progress');

-- Insert demo notifications
INSERT INTO notifications (user_id, title, message, type, action_url) VALUES
(3, 'Payment Due Reminder', 'Your rent payment for October 2024 is due in 3 days', 'warning', '/billing/'),
(4, 'Application Update', 'Your application for Mike\'s Tech Hub is now under review', 'info', '/applications/my-applications.php'),
(1, 'New Application', 'New application received for Training Center space', 'info', '/admin/applications.php');

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'Mega School Plaza', 'Website name'),
('admin_email', 'admin@megaplaza.com', 'Administrator email address'),
('late_fee_percentage', '5', 'Late fee percentage for overdue payments'),
('grace_period_days', '5', 'Grace period before late fees apply'),
('max_file_size', '10485760', 'Maximum file upload size in bytes (10MB)'),
('allowed_file_types', 'jpg,jpeg,png,pdf,doc,docx', 'Allowed file extensions for uploads');

-- Create uploads directory structure (for reference)
-- You'll need to create these directories manually in your XAMPP htdocs folder:
-- uploads/
-- uploads/applications/
-- uploads/payments/
-- uploads/documents/
-- uploads/receipts/

-- Grant privileges (adjust as needed for your setup)
-- GRANT ALL PRIVILEGES ON megaplaza.* TO 'megaplaza_user'@'localhost' IDENTIFIED BY 'your_password';
-- FLUSH PRIVILEGES;

-- Database setup complete
SELECT 'Database setup completed successfully!' as Status;
