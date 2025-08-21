-- Seed Data for Mega School Plaza Management System
-- Version 1.0

USE mega_school_plaza;

-- Insert default admin user
INSERT INTO users (first_name, last_name, email, password, role, status, email_verified) VALUES
('System', 'Administrator', 'admin@megaschoolplaza.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 1),
('John', 'Manager', 'manager@megaschoolplaza.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'active', 1),
('Jane', 'Tenant', 'tenant@megaschoolplaza.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'active', 1);
-- Default password for all demo accounts: "password"

-- Insert shop categories
INSERT INTO categories (name, description, icon, color, sort_order) VALUES
('Retail Store', 'General retail and merchandise shops', 'fas fa-shopping-bag', '#d4a574', 1),
('Restaurant', 'Food service and dining establishments', 'fas fa-utensils', '#e8a298', 2),
('Services', 'Professional and personal services', 'fas fa-handshake', '#8b7355', 3),
('Technology', 'Electronics, computers, and tech services', 'fas fa-laptop', '#c4956c', 4),
('Health & Beauty', 'Salons, spas, and wellness centers', 'fas fa-heart', '#a68b5b', 5),
('Education', 'Training centers and educational services', 'fas fa-graduation-cap', '#d4a574', 6),
('Entertainment', 'Gaming, recreation, and entertainment', 'fas fa-gamepad', '#e8a298', 7),
('Office Space', 'Professional offices and co-working spaces', 'fas fa-building', '#8b7355', 8);

-- Insert amenities
INSERT INTO amenities (name, description, icon) VALUES
('Air Conditioning', 'Climate controlled environment', 'fas fa-snowflake'),
('High Speed Internet', 'Fiber optic internet connection', 'fas fa-wifi'),
('Parking Space', 'Dedicated parking spot included', 'fas fa-car'),
('Storage Room', 'Additional storage space', 'fas fa-boxes'),
('Security System', '24/7 security monitoring', 'fas fa-shield-alt'),
('Loading Dock', 'Easy access for deliveries', 'fas fa-truck'),
('Display Windows', 'Street-facing display windows', 'fas fa-eye'),
('Kitchen Facilities', 'Commercial kitchen setup', 'fas fa-utensils'),
('Restroom', 'Private restroom facilities', 'fas fa-restroom'),
('Elevator Access', 'Elevator access to upper floors', 'fas fa-elevator');

-- Insert sample shops
INSERT INTO shops (shop_number, title, description, category_id, floor_number, area_sqft, monthly_rent, security_deposit, status, features, utilities_included, parking_spaces, air_conditioning, heating, internet_ready, security_system) VALUES
('A101', 'Prime Corner Retail Space', 'Excellent corner location with high foot traffic and large display windows', 1, 1, 1200.00, 2500.00, 5000.00, 'available', '["Corner location", "Large windows", "High ceiling"]', 'Water, Electricity, Trash', 2, 1, 1, 1, 1),
('A102', 'Modern Restaurant Space', 'Fully equipped restaurant space with commercial kitchen and dining area', 2, 1, 1800.00, 4200.00, 8400.00, 'available', '["Commercial kitchen", "Dining area", "Grease trap"]', 'Water, Electricity, Gas, Trash', 3, 1, 1, 1, 1),
('A103', 'Professional Office Suite', 'Modern office space perfect for professional services', 8, 1, 800.00, 1800.00, 3600.00, 'occupied', '["Reception area", "Private offices", "Conference room"]', 'Water, Electricity, Internet, Trash', 2, 1, 1, 1, 1),
('B201', 'Tech Hub Space', 'Open concept space ideal for technology companies', 4, 2, 1500.00, 3200.00, 6400.00, 'available', '["Open floor plan", "Server room", "High-speed internet"]', 'Water, Electricity, Internet, Trash', 4, 1, 1, 1, 1),
('B202', 'Beauty Salon Suite', 'Elegant space designed for beauty and wellness services', 5, 2, 1000.00, 2200.00, 4400.00, 'available', '["Salon stations", "Wash area", "Reception"]', 'Water, Electricity, Trash', 2, 1, 1, 1, 1),
('B203', 'Educational Center', 'Multi-room space perfect for training and education', 6, 2, 1300.00, 2800.00, 5600.00, 'maintenance', '["Multiple classrooms", "Audio/visual equipment", "Storage"]', 'Water, Electricity, Internet, Trash', 3, 1, 1, 1, 1),
('C301', 'Entertainment Venue', 'Large open space suitable for entertainment and events', 7, 3, 2000.00, 4500.00, 9000.00, 'available', '["Large open space", "Sound system", "Stage area"]', 'Water, Electricity, Trash', 5, 1, 1, 1, 1),
('C302', 'Boutique Retail Space', 'Intimate retail space perfect for specialty shops', 1, 3, 600.00, 1500.00, 3000.00, 'reserved', '["Boutique layout", "Display areas", "Storage"]', 'Water, Electricity, Trash', 1, 1, 1, 1, 0);

-- Link shops with amenities
INSERT INTO shop_amenities (shop_id, amenity_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 7), (1, 5), -- A101
(2, 1), (2, 2), (2, 3), (2, 8), (2, 9), (2, 5), -- A102
(3, 1), (3, 2), (3, 3), (3, 9), (3, 10), (3, 5), -- A103
(4, 1), (4, 2), (4, 3), (4, 4), (4, 10), (4, 5), -- B201
(5, 1), (5, 2), (5, 3), (5, 9), (5, 10), (5, 5), -- B202
(6, 1), (6, 2), (6, 3), (6, 4), (6, 10), (6, 5), -- B203
(7, 1), (7, 2), (7, 3), (7, 4), (7, 6), (7, 5), -- C301
(8, 1), (8, 2), (8, 3), (8, 7), (8, 10), (8, 5); -- C302

-- Insert sample application
INSERT INTO applications (user_id, shop_id, business_name, business_type, business_description, years_in_business, monthly_revenue, employees_count, status, move_in_date, lease_duration) VALUES
(3, 1, 'Tech Solutions Inc', 'Technology Services', 'We provide IT consulting and software development services for small businesses', 3, 15000.00, 5, 'approved', '2024-02-01', 24);

-- Insert sample lease
INSERT INTO leases (application_id, user_id, shop_id, lease_number, start_date, end_date, monthly_rent, security_deposit, status, signed_date) VALUES
(1, 3, 3, 'LSE-2024-001', '2024-01-01', '2025-12-31', 1800.00, 3600.00, 'active', '2023-12-15');

-- Insert sample payments
INSERT INTO payments (lease_id, user_id, invoice_number, payment_type, amount, due_date, status) VALUES
(1, 3, 'INV-2024-001', 'rent', 1800.00, '2024-01-01', 'paid'),
(1, 3, 'INV-2024-002', 'rent', 1800.00, '2024-02-01', 'paid'),
(1, 3, 'INV-2024-003', 'rent', 1800.00, '2024-03-01', 'pending');

-- Insert system settings
INSERT INTO settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_name', 'Mega School Plaza', 'string', 'Website name', 1),
('site_description', 'Premium Shop Rental & Management Platform', 'string', 'Website description', 1),
('contact_email', 'info@megaschoolplaza.com', 'string', 'Main contact email', 1),
('contact_phone', '(555) 123-4567', 'string', 'Main contact phone', 1),
('address', '123 Plaza Street, Business District, City, State 12345', 'string', 'Physical address', 1),
('late_fee_amount', '50.00', 'number', 'Default late fee amount', 0),
('grace_period_days', '5', 'number', 'Grace period for rent payments', 0),
('max_file_size', '5242880', 'number', 'Maximum file upload size in bytes', 0),
('maintenance_enabled', 'true', 'boolean', 'Enable maintenance request system', 0),
('email_notifications', 'true', 'boolean', 'Enable email notifications', 0);

-- Insert sample notifications
INSERT INTO notifications (user_id, title, message, type, category) VALUES
(3, 'Welcome to Mega School Plaza', 'Welcome to our shop management platform! Your account has been successfully created.', 'success', 'account'),
(3, 'Rent Payment Due', 'Your rent payment for March 2024 is due on March 1st. Amount: $1,800.00', 'warning', 'payment'),
(1, 'New Application Received', 'A new shop rental application has been submitted for review.', 'info', 'application'),
(2, 'Maintenance Request', 'A new maintenance request has been submitted for Shop A103.', 'info', 'maintenance');
