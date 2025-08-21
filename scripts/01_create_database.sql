-- Create Database Schema for Mega School Plaza Management System
-- Version 1.0

-- Create database
CREATE DATABASE IF NOT EXISTS mega_school_plaza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mega_school_plaza;

-- Users table (tenants, managers, admins)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('tenant', 'manager', 'admin') DEFAULT 'tenant',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    profile_image VARCHAR(255),
    date_of_birth DATE,
    address TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Shop categories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(7),
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Amenities
CREATE TABLE amenities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Shops/Units
CREATE TABLE shops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shop_number VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    floor_number INT,
    area_sqft DECIMAL(8,2),
    monthly_rent DECIMAL(10,2) NOT NULL,
    security_deposit DECIMAL(10,2),
    status ENUM('available', 'occupied', 'maintenance', 'reserved') DEFAULT 'available',
    features TEXT, -- JSON format for additional features
    images TEXT, -- JSON format for image paths
    floor_plan VARCHAR(255),
    utilities_included TEXT,
    parking_spaces INT DEFAULT 0,
    storage_space TINYINT(1) DEFAULT 0,
    air_conditioning TINYINT(1) DEFAULT 0,
    heating TINYINT(1) DEFAULT 0,
    internet_ready TINYINT(1) DEFAULT 0,
    security_system TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Shop amenities (many-to-many relationship)
CREATE TABLE shop_amenities (
    shop_id INT,
    amenity_id INT,
    PRIMARY KEY (shop_id, amenity_id),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
);

-- Applications for shop rentals
CREATE TABLE applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    shop_id INT NOT NULL,
    business_name VARCHAR(200),
    business_type VARCHAR(100),
    business_description TEXT,
    years_in_business INT,
    monthly_revenue DECIMAL(12,2),
    employees_count INT,
    references TEXT, -- JSON format for business references
    documents TEXT, -- JSON format for uploaded documents
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'withdrawn') DEFAULT 'pending',
    admin_notes TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    move_in_date DATE,
    lease_duration INT, -- months
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Leases (active rental agreements)
CREATE TABLE leases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    shop_id INT NOT NULL,
    lease_number VARCHAR(50) UNIQUE NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    monthly_rent DECIMAL(10,2) NOT NULL,
    security_deposit DECIMAL(10,2),
    late_fee DECIMAL(8,2) DEFAULT 50.00,
    grace_period_days INT DEFAULT 5,
    auto_renewal TINYINT(1) DEFAULT 0,
    renewal_notice_days INT DEFAULT 60,
    status ENUM('active', 'expired', 'terminated', 'renewed') DEFAULT 'active',
    terms_conditions TEXT,
    special_clauses TEXT,
    signed_date DATE,
    lease_document VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- Payments and billing
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lease_id INT NOT NULL,
    user_id INT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    payment_type ENUM('rent', 'deposit', 'late_fee', 'maintenance', 'utility', 'other') DEFAULT 'rent',
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    paid_date DATE NULL,
    payment_method ENUM('cash', 'check', 'bank_transfer', 'credit_card', 'online') NULL,
    transaction_id VARCHAR(100),
    status ENUM('pending', 'paid', 'overdue', 'partial', 'cancelled') DEFAULT 'pending',
    late_fee_applied DECIMAL(8,2) DEFAULT 0,
    notes TEXT,
    receipt_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lease_id) REFERENCES leases(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Maintenance requests
CREATE TABLE maintenance_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    shop_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    category VARCHAR(100),
    status ENUM('open', 'in_progress', 'completed', 'cancelled') DEFAULT 'open',
    assigned_to INT,
    estimated_cost DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    scheduled_date DATE,
    completed_date DATE,
    images TEXT, -- JSON format for image paths
    admin_notes TEXT,
    tenant_rating INT, -- 1-5 rating after completion
    tenant_feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Documents storage
CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    application_id INT,
    lease_id INT,
    maintenance_id INT,
    document_type VARCHAR(100) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    description TEXT,
    is_public TINYINT(1) DEFAULT 0,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (lease_id) REFERENCES leases(id) ON DELETE CASCADE,
    FOREIGN KEY (maintenance_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Notifications
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    category VARCHAR(50),
    is_read TINYINT(1) DEFAULT 0,
    action_url VARCHAR(500),
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- System settings
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public TINYINT(1) DEFAULT 0,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Audit log for tracking changes
CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    table_name VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('create', 'update', 'delete') NOT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_shops_status ON shops(status);
CREATE INDEX idx_shops_category ON shops(category_id);
CREATE INDEX idx_applications_user ON applications(user_id);
CREATE INDEX idx_applications_shop ON applications(shop_id);
CREATE INDEX idx_applications_status ON applications(status);
CREATE INDEX idx_leases_user ON leases(user_id);
CREATE INDEX idx_leases_shop ON leases(shop_id);
CREATE INDEX idx_leases_status ON leases(status);
CREATE INDEX idx_payments_lease ON payments(lease_id);
CREATE INDEX idx_payments_user ON payments(user_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_payments_due_date ON payments(due_date);
CREATE INDEX idx_maintenance_user ON maintenance_requests(user_id);
CREATE INDEX idx_maintenance_shop ON maintenance_requests(shop_id);
CREATE INDEX idx_maintenance_status ON maintenance_requests(status);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
CREATE INDEX idx_audit_log_table_record ON audit_log(table_name, record_id);
